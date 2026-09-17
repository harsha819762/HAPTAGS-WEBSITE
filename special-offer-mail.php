<?php
/**
 * HAPTAGS LLP — "Special Offer" Website Development Enquiry Mailer
 * Target Destination: info@haptags.com
 * SMTP Host: smtp.hostinger.com
 *
 * Active window (server-enforced, cannot be bypassed from the browser):
 *   21 Sept 2026, 12:00 AM IST  ->  23 Sept 2026, 12:00 AM IST  (48 hours)
 *
 * Instructions:
 * 1. Place this file in your website root directory on Hostinger, next to send-mail.php.
 * 2. Update SMTP_PASS below with your info@haptags.com email password.
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Method not allowed. Only POST requests are accepted."]);
    exit;
}

// -----------------------------------------------------------------------------
// 1. OFFER WINDOW — hard server-side enforcement.
//    Client-side gating in haptags-form.html is UX only; this is the real gate.
// -----------------------------------------------------------------------------
date_default_timezone_set('Asia/Kolkata');
$offerStart = strtotime('2026-09-21 00:00:00');
$offerEnd   = strtotime('2026-09-23 00:00:00');
$now = time();

if ($now < $offerStart || $now >= $offerEnd) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "error" => "This special offer enquiry form is only open from 21 Sept 2026, 12:00 AM to 23 Sept 2026, 12:00 AM (IST)."
    ]);
    exit;
}

// -----------------------------------------------------------------------------
// 2. CONFIGURATION (Hostinger Mail Settings — same mailbox as the main site)
// -----------------------------------------------------------------------------
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465); // 465 for SSL, 587 for TLS
define('SMTP_SECURE', 'ssl'); // 'ssl' or 'tls'
define('SMTP_USER', 'info@haptags.com');
define('SMTP_PASS', 'YOUR_HOSTINGER_EMAIL_PASSWORD'); // <-- ENTER YOUR HOSTINGER EMAIL PASSWORD HERE

define('RECEIVER_EMAIL', 'info@haptags.com');
define('RECEIVER_NAME', 'Haptags LLP Special Offer Leads');
define('SENDER_EMAIL', 'info@haptags.com');
define('SENDER_NAME', 'Haptags Website Portal');

define('MAX_ATTACHMENT_BYTES', 5 * 1024 * 1024); // 5 MB
$ALLOWED_ATTACHMENT_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

// First-25 free-website coupon promo. Storage file sits in the site root but
// is unreachable over HTTP — .htaccess already denies any *.json request.
define('COUPON_STORE_FILE', __DIR__ . '/special-offer-coupons.json');
define('COUPON_WINNER_LIMIT', 25);

// -----------------------------------------------------------------------------
// 3. SMTP SENDER (same handshake as send-mail.php, plus MIME attachment support)
// -----------------------------------------------------------------------------
class SpecialOfferSMTP {
    private $host;
    private $port;
    private $secure;
    private $username;
    private $password;
    private $socket = null;

    public function __construct($host, $port, $secure, $username, $password) {
        $this->host = $host;
        $this->port = $port;
        $this->secure = strtolower($secure);
        $this->username = $username;
        $this->password = $password;
    }

    private function readResponse() {
        $response = "";
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == " ") {
                break;
            }
        }
        return $response;
    }

    private function sendCommand($cmd, $expectedCode) {
        fputs($this->socket, $cmd . "\r\n");
        $response = $this->readResponse();
        $code = substr($response, 0, 3);
        if ($code != $expectedCode) {
            throw new Exception("SMTP Error: Expected $expectedCode but got $response");
        }
        return $response;
    }

    private function writeRaw($data) {
        $len = strlen($data);
        $written = 0;
        while ($written < $len) {
            $chunk = fwrite($this->socket, substr($data, $written, 8192));
            if ($chunk === false) {
                throw new Exception("Failed writing message body to SMTP socket");
            }
            $written += $chunk;
        }
    }

    // $attachments: array of ['filename' => ..., 'mime' => ..., 'data' => raw bytes]
    public function send($to, $toName, $from, $fromName, $replyTo, $subject, $htmlBody, $plainBody, $attachments = []) {
        $prefix = ($this->secure === 'ssl') ? 'ssl://' : '';
        $timeout = 15;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $this->socket = @stream_socket_client($prefix . $this->host . ':' . $this->port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$this->socket) {
            throw new Exception("Could not connect to SMTP server {$this->host}:{$this->port} ($errno: $errstr)");
        }

        $this->readResponse();

        $this->sendCommand("EHLO " . (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost'), 250);

        if ($this->secure === 'tls') {
            $this->sendCommand("STARTTLS", 220);
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->sendCommand("EHLO " . (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost'), 250);
        }

        $this->sendCommand("AUTH LOGIN", 334);
        $this->sendCommand(base64_encode($this->username), 334);
        $this->sendCommand(base64_encode($this->password), 235);

        $this->sendCommand("MAIL FROM: <{$from}>", 250);
        $this->sendCommand("RCPT TO: <{$to}>", 250);
        $this->sendCommand("DATA", 354);

        $mixedBoundary = "----=_MixedPart_" . md5(uniqid('', true));
        $altBoundary   = "----=_AltPart_" . md5(uniqid('', true));

        $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n";
        $headers .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$to}>\r\n";
        if (!empty($replyTo)) {
            $headers .= "Reply-To: <{$replyTo}>\r\n";
        }
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$mixedBoundary}\"\r\n";
        $headers .= "X-Mailer: Haptags LLP Special Offer Mailer\r\n";

        $message  = $headers . "\r\n";
        $message .= "--{$mixedBoundary}\r\n";
        $message .= "Content-Type: multipart/alternative; boundary=\"{$altBoundary}\"\r\n\r\n";

        $message .= "--{$altBoundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($plainBody));

        $message .= "--{$altBoundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($htmlBody));
        $message .= "--{$altBoundary}--\r\n";

        foreach ($attachments as $att) {
            $safeName = str_replace(['"', "\r", "\n"], '', $att['filename']);
            $message .= "--{$mixedBoundary}\r\n";
            $message .= "Content-Type: " . $att['mime'] . "; name=\"" . $safeName . "\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment; filename=\"" . $safeName . "\"\r\n\r\n";
            $message .= chunk_split(base64_encode($att['data']));
        }

        $message .= "--{$mixedBoundary}--\r\n";

        // RFC 5321 dot-stuffing: escape lines that begin with "." so the
        // lone-dot terminator below unambiguously ends the DATA section.
        $message = preg_replace('/\r\n\./', "\r\n..", $message);

        $this->writeRaw($message);
        $this->writeRaw("\r\n.\r\n");

        $response = $this->readResponse();
        if (substr($response, 0, 3) != '250') {
            throw new Exception("SMTP Error on DATA terminator: " . $response);
        }

        $this->sendCommand("QUIT", 221);
        fclose($this->socket);
        return true;
    }
}

// -----------------------------------------------------------------------------
// 4. READ & VALIDATE SUBMITTED FIELDS
// -----------------------------------------------------------------------------
$data = $_POST;

// Honeypot spam protection
if (!empty($data['_gotcha'])) {
    echo json_encode(["success" => true, "message" => "Enquiry received."]);
    exit;
}

$name        = isset($data['full_name']) ? trim(strip_tags($data['full_name'])) : '';
$email       = isset($data['email']) ? trim(filter_var($data['email'], FILTER_SANITIZE_EMAIL)) : '';
$contact     = isset($data['contact_number']) ? trim(strip_tags($data['contact_number'])) : '';
$org         = isset($data['organisation_name']) ? trim(strip_tags($data['organisation_name'])) : '';
$address     = isset($data['address']) ? trim(strip_tags($data['address'])) : '';
$govtIdNum   = isset($data['govt_id_number']) ? trim(strip_tags($data['govt_id_number'])) : '';
$others      = isset($data['others']) ? trim(strip_tags($data['others'])) : '';

if (empty($name) || empty($email) || empty($contact)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Full name, email and contact number are required fields."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Invalid email address format."]);
    exit;
}

// -----------------------------------------------------------------------------
// 5. OPTIONAL GOVT ID FILE ATTACHMENT
// -----------------------------------------------------------------------------
// Detect the real MIME type from file content (never trust the client-supplied
// Content-Type). Prefers the fileinfo extension, but that extension is not
// guaranteed to be enabled on every host, so this falls back to sniffing the
// magic bytes of the file types this form actually accepts.
function detectSpecialOfferMimeType($path) {
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $type = $finfo->file($path);
        if ($type) {
            return $type;
        }
    }
    if (function_exists('mime_content_type')) {
        $type = @mime_content_type($path);
        if ($type) {
            return $type;
        }
    }

    $handle = @fopen($path, 'rb');
    if ($handle === false) {
        return null;
    }
    $head = fread($handle, 12);
    fclose($handle);

    if (substr($head, 0, 8) === "\x89PNG\r\n\x1a\n") {
        return 'image/png';
    }
    if (substr($head, 0, 3) === "\xFF\xD8\xFF") {
        return 'image/jpeg';
    }
    if (substr($head, 0, 4) === 'RIFF' && substr($head, 8, 4) === 'WEBP') {
        return 'image/webp';
    }
    if (substr($head, 0, 4) === '%PDF') {
        return 'application/pdf';
    }
    return null;
}

$attachments = [];
if (isset($_FILES['govt_id_file']) && $_FILES['govt_id_file']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['govt_id_file'];

    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "The uploaded ID file is too large. Please keep it under 5MB."]);
        exit;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "The ID file could not be uploaded. Please try again."]);
        exit;
    }

    if ($file['size'] > MAX_ATTACHMENT_BYTES) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "The uploaded ID file is too large. Please keep it under 5MB."]);
        exit;
    }

    $detectedType = detectSpecialOfferMimeType($file['tmp_name']);

    if ($detectedType === null || !in_array($detectedType, $ALLOWED_ATTACHMENT_TYPES, true)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "The ID file must be a JPG, PNG or PDF."]);
        exit;
    }

    $fileData = file_get_contents($file['tmp_name']);
    if ($fileData !== false) {
        $attachments[] = [
            'filename' => basename($file['name']),
            'mime'     => $detectedType,
            'data'     => $fileData
        ];
    }
}

$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
$timestamp = date('l, F j, Y \a\t g:i A \I\S\T');

// -----------------------------------------------------------------------------
// 6. FIRST-25 FREE-WEBSITE COUPON ALLOCATION
//    File-locked so two simultaneous submissions can never both land on the
//    same sequence number (and both get told they're "#25").
// -----------------------------------------------------------------------------
function generateCouponCode($sequenceNumber) {
    // Excludes 0/O/1/I so a hand-copied or photographed code isn't ambiguous.
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $rand = '';
    $bytes = random_bytes(6);
    for ($i = 0; $i < 6; $i++) {
        $rand .= $chars[ord($bytes[$i]) % strlen($chars)];
    }
    return 'HAPTAGS-FREEWEB-' . str_pad($sequenceNumber, 2, '0', STR_PAD_LEFT) . '-' . $rand;
}

function allocateCoupon($name, $email, $contact) {
    $fp = @fopen(COUPON_STORE_FILE, 'c+');
    if (!$fp) {
        // Storage unavailable — fail safe as "not a winner" rather than block the enquiry.
        return ['winner' => false, 'sequence' => null, 'code' => null];
    }

    flock($fp, LOCK_EX);

    $raw = stream_get_contents($fp);
    $store = json_decode($raw, true);
    if (!is_array($store) || !isset($store['count'])) {
        $store = ['count' => 0, 'issued' => []];
    }

    $store['count'] += 1;
    $sequence = $store['count'];
    $winner = $sequence <= COUPON_WINNER_LIMIT;
    $code = null;

    if ($winner) {
        $code = generateCouponCode($sequence);
        $store['issued'][] = [
            'sequence'  => $sequence,
            'code'      => $code,
            'name'      => $name,
            'email'     => $email,
            'contact'   => $contact,
            'issued_at' => date('c')
        ];
    }

    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($store, JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return ['winner' => $winner, 'sequence' => $sequence, 'code' => $code];
}

// Sends the "you've won" email to the customer's own address, from
// info@haptags.com. Best-effort: the on-screen scratch ticket already shows
// the code regardless of whether this delivers, so failure here is silent.
function sendWinnerCongratsEmail($toEmail, $toName, $code, $sequence) {
    $subject = "🎉 Congratulations! You've Won a Free Website — Haptags Special Offer";
    $qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=" . urlencode($code);

    $htmlBody = "
<!DOCTYPE html>
<html>
<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>{$subject}</title>
</head>
<body style='margin:0; padding:0; background-color:#0b0f17; font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;'>
  <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background-color:#0b0f17; padding:24px 12px;'>
    <tr><td align='center'>
      <table role='presentation' width='100%' style='max-width:480px; background-color:#111827; border-radius:14px; overflow:hidden; box-shadow:0 10px 25px rgba(0,0,0,0.5);'>
        <tr><td style='background:linear-gradient(135deg,#1e1b4b 0%,#0f172a 100%); padding:28px; text-align:center; border-bottom:1px solid #312e81;'>
          <div style='font-size:22px; font-weight:800; color:#ffffff; letter-spacing:-0.5px; text-transform:uppercase;'>HAPTAGS</div>
          <div style='color:#94a3b8; font-size:12px; margin-top:6px;'>Special Offer &bull; Website Development Enquiry</div>
        </td></tr>
        <tr><td style='padding:30px 26px; text-align:center;'>
          <div style='font-size:28px; margin-bottom:6px;'>🎉</div>
          <h1 style='color:#ffffff; font-size:21px; margin:0 0 8px;'>Congratulations, " . htmlspecialchars($toName) . "!</h1>
          <p style='color:#cbd5e1; font-size:14px; line-height:1.6; margin:0 0 22px;'>
            You're enquiry <strong style='color:#f0dfb8;'>#{$sequence}</strong> of our first 25 special-offer submissions —
            which means you've unlocked a <strong style='color:#f0dfb8;'>FREE WEBSITE build</strong> from Haptags LLP.
          </p>

          <table role='presentation' width='100%' style='background:linear-gradient(135deg,#FFE59A 0%,#F0C64A 25%,#D4A017 60%,#8B6914 100%); border-radius:14px;'>
            <tr><td style='padding:24px; text-align:center;'>
              <div style='font-size:11px; font-weight:800; letter-spacing:1px; text-transform:uppercase; color:#0B1F3A; margin-bottom:14px;'>Your Winning Ticket</div>
              <div style='background:#ffffff; border-radius:10px; padding:16px; display:inline-block;'>
                <img src='{$qrImageUrl}' width='200' height='200' alt='QR code for coupon {$code}' style='display:block; margin:0 auto;' />
                <div style='font-family:\"Courier New\",monospace; font-weight:700; font-size:15px; letter-spacing:0.5px; color:#0B1F3A; margin-top:12px;'>" . htmlspecialchars($code) . "</div>
              </div>
            </td></tr>
          </table>

          <p style='color:#94a3b8; font-size:13px; line-height:1.6; margin:22px 0 0; text-align:left;'>
            <strong style='color:#e2e8f0;'>To redeem:</strong> Reply to this email or WhatsApp us at
            <a href='tel:+919986861402' style='color:#34d399; text-decoration:none;'>+91 9986861402</a> with a screenshot of this code.
            Our team will verify it and reach out to kick off your free website build.
          </p>
        </td></tr>
        <tr><td style='background-color:#0b0f17; padding:20px; text-align:center; font-size:12px; color:#64748b; border-top:1px solid #1f293d;'>
          <strong>Haptags LLP</strong> &bull; Near Maheshwarramma temple road, Mahadevapura, Bangalore - 560048<br>
          info@haptags.com
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
";

    $plainBody = "CONGRATULATIONS, {$toName}!\n\n"
        . "You're enquiry #{$sequence} of our first 25 special-offer submissions — you've unlocked a FREE WEBSITE build from Haptags LLP.\n\n"
        . "Your coupon code: {$code}\n\n"
        . "To redeem: reply to this email or WhatsApp us at +91 9986861402 with a screenshot of this code. Our team will verify it and reach out to kick off your build.\n\n"
        . "Haptags LLP\nNear Maheshwarramma temple road, Mahadevapura, Bangalore - 560048\ninfo@haptags.com";

    $sent = false;
    if (defined('SMTP_PASS') && SMTP_PASS !== 'YOUR_HOSTINGER_EMAIL_PASSWORD' && !empty(SMTP_PASS)) {
        try {
            $smtp = new SpecialOfferSMTP(SMTP_HOST, SMTP_PORT, SMTP_SECURE, SMTP_USER, SMTP_PASS);
            $sent = $smtp->send(
                $toEmail,
                $toName,
                SENDER_EMAIL,
                SENDER_NAME,
                RECEIVER_EMAIL,
                $subject,
                $htmlBody,
                $plainBody
            );
        } catch (Exception $e) {
            $sent = false;
        }
    }

    if (!$sent) {
        $boundary = "----=_NextPart_" . md5(microtime() . rand());
        $headers  = "From: " . SENDER_NAME . " <" . SENDER_EMAIL . ">\r\n";
        $headers .= "Reply-To: " . RECEIVER_EMAIL . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        $mailContent  = "--{$boundary}\r\n";
        $mailContent .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $mailContent .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $mailContent .= $plainBody . "\r\n\r\n";
        $mailContent .= "--{$boundary}\r\n";
        $mailContent .= "Content-Type: text/html; charset=UTF-8\r\n";
        $mailContent .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $mailContent .= $htmlBody . "\r\n\r\n";
        $mailContent .= "--{$boundary}--";

        @mail($toEmail, $subject, $mailContent, $headers);
    }
}

// Coupon allocation happens only after the email below is confirmed sent (see
// section 8) — never before. Allocating first would let a delivery failure
// silently burn a real winner's slot with no way for them to find out, and a
// retry would then push them to a worse sequence number.

// -----------------------------------------------------------------------------
// 7. EMAIL TEMPLATE
// -----------------------------------------------------------------------------
$subject = "🎯 [Special Offer Lead] {$name} - Website Development Enquiry";

$htmlBody = "
<!DOCTYPE html>
<html>
<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>{$subject}</title>
  <style>
    body { margin: 0; padding: 0; background-color: #0b0f17; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #e2e8f0; }
    .email-container { max-width: 620px; margin: 20px auto; background-color: #111827; border: 1px solid #1f293d; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
    .email-header { background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%); padding: 30px; text-align: center; border-bottom: 1px solid #312e81; }
    .email-logo { font-size: 24px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px; text-transform: uppercase; }
    .email-badge { display: inline-block; background: #b8902e; color: #0f172a; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 4px; margin-left: 6px; }
    .email-subhead { color: #94a3b8; font-size: 13px; margin-top: 6px; }
    .email-body { padding: 32px 28px; }
    .alert-banner { background: rgba(184, 144, 46, 0.12); border-left: 4px solid #b8902e; padding: 14px 18px; border-radius: 6px; margin-bottom: 24px; color: #f0dfb8; font-size: 14px; }
    .data-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .data-table td { padding: 12px 14px; border-bottom: 1px solid #1f293d; font-size: 14px; }
    .data-table td.label { width: 35%; color: #94a3b8; font-weight: 600; vertical-align: top; }
    .data-table td.value { color: #f8fafc; font-weight: 500; }
    .message-box { background-color: #0d131f; border: 1px solid #1e293b; border-radius: 8px; padding: 18px; color: #cbd5e1; font-size: 14px; line-height: 1.6; white-space: pre-wrap; margin-top: 8px; }
    .btn-action { display: inline-block; background: #1B6E75; color: #ffffff !important; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; margin-top: 15px; }
    .email-footer { background-color: #0b0f17; padding: 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #1f293d; }
  </style>
</head>
<body>
  <div class='email-container'>
    <div class='email-header'>
      <div class='email-logo'>HAPTAGS <span class='email-badge'>SPECIAL OFFER</span></div>
      <div class='email-subhead'>Website Development Enquiry Form</div>
      <h2 style='color:#ffffff; margin: 16px 0 0 0; font-size: 20px;'>New Special Offer Lead</h2>
    </div>

    <div class='email-body'>
      <div class='alert-banner'>
        🎯 <strong>New Lead Notification:</strong> A prospective client submitted the 48-hour special-offer enquiry form on <strong>haptags.com</strong>.
      </div>

      <table class='data-table'>
        <tr><td class='label'>Full Name:</td><td class='value' style='font-size:16px; font-weight:700; color:#ffffff;'>" . htmlspecialchars($name) . "</td></tr>
        <tr><td class='label'>Email Address:</td><td class='value'><a href='mailto:" . htmlspecialchars($email) . "' style='color:#38bdf8; text-decoration:none; font-weight:600;'>" . htmlspecialchars($email) . "</a></td></tr>
        <tr><td class='label'>Contact Number:</td><td class='value'><a href='tel:" . htmlspecialchars($contact) . "' style='color:#34d399; text-decoration:none; font-weight:600;'>" . htmlspecialchars($contact) . "</a></td></tr>
        <tr><td class='label'>Organisation:</td><td class='value'>" . (!empty($org) ? htmlspecialchars($org) : 'Not provided') . "</td></tr>
        <tr><td class='label'>Address:</td><td class='value'>" . (!empty($address) ? nl2br(htmlspecialchars($address)) : 'Not provided') . "</td></tr>
        <tr><td class='label'>Govt ID Number:</td><td class='value'>" . (!empty($govtIdNum) ? htmlspecialchars($govtIdNum) : 'Not provided') . "</td></tr>
        <tr><td class='label'>ID Copy Attached:</td><td class='value'>" . (!empty($attachments) ? 'Yes — see attachment' : 'No') . "</td></tr>
        <tr><td class='label'>Submission Time:</td><td class='value' style='color:#94a3b8; font-size:13px;'>" . $timestamp . "</td></tr>
      </table>

      " . (!empty($others) ? "
      <div style='margin-top: 15px;'>
        <div style='color: #94a3b8; font-weight: 600; font-size: 13px; margin-bottom: 6px;'>OTHERS / PROJECT DETAILS:</div>
        <div class='message-box'>" . nl2br(htmlspecialchars($others)) . "</div>
      </div>
      " : "") . "

      <div style='text-align: center; margin-top: 28px;'>
        <a href='mailto:" . htmlspecialchars($email) . "?subject=" . urlencode("Re: Your Special Offer Enquiry with Haptags LLP") . "' class='btn-action'>
          ✉️ Reply to " . htmlspecialchars($name) . "
        </a>
      </div>
    </div>

    <div class='email-footer'>
      <strong>Haptags LLP</strong> &bull; Near Maheshwarramma temple road, Mahadevapura, Bangalore - 560048<br>
      Special-offer form active 21-23 Sept 2026 &bull; Client IP: " . htmlspecialchars($clientIP) . "
    </div>
  </div>
</body>
</html>
";

$plainBody = "
============================================================
HAPTAGS LLP — NEW SPECIAL OFFER LEAD (Website Development Enquiry)
============================================================

Full Name: {$name}
Email Address: {$email}
Contact Number: {$contact}
Organisation: " . (!empty($org) ? $org : 'Not provided') . "
Address: " . (!empty($address) ? $address : 'Not provided') . "
Govt ID Number: " . (!empty($govtIdNum) ? $govtIdNum : 'Not provided') . "
ID Copy Attached: " . (!empty($attachments) ? 'Yes' : 'No') . "
Submitted At: {$timestamp}
Client IP: {$clientIP}

OTHERS / PROJECT DETAILS:
------------------------------------------------------------
" . (!empty($others) ? $others : "No additional notes provided.") . "
------------------------------------------------------------

Haptags LLP — info@haptags.com
Near Maheshwarramma temple road, Mahadevapura, Bangalore - 560048
";

// -----------------------------------------------------------------------------
// 8. SEND EMAIL VIA HOSTINGER SMTP, WITH mail() FALLBACK (no attachment support)
// -----------------------------------------------------------------------------
$sent = false;
$errorMsg = "";

if (defined('SMTP_PASS') && SMTP_PASS !== 'YOUR_HOSTINGER_EMAIL_PASSWORD' && !empty(SMTP_PASS)) {
    try {
        $smtp = new SpecialOfferSMTP(SMTP_HOST, SMTP_PORT, SMTP_SECURE, SMTP_USER, SMTP_PASS);
        $sent = $smtp->send(
            RECEIVER_EMAIL,
            RECEIVER_NAME,
            SENDER_EMAIL,
            SENDER_NAME,
            $email,
            $subject,
            $htmlBody,
            $plainBody,
            $attachments
        );
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
}

if (!$sent) {
    // Fallback has no attachment support; the lead details still reach the inbox.
    $boundary = "----=_NextPart_" . md5(time() . rand());
    $headers  = "From: " . SENDER_NAME . " <" . SENDER_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $mailContent  = "--{$boundary}\r\n";
    $mailContent .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $mailContent .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $mailContent .= $plainBody . "\r\n\r\n";
    $mailContent .= "--{$boundary}\r\n";
    $mailContent .= "Content-Type: text/html; charset=UTF-8\r\n";
    $mailContent .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $mailContent .= $htmlBody . "\r\n\r\n";
    $mailContent .= "--{$boundary}--";

    $sent = @mail(RECEIVER_EMAIL, $subject, $mailContent, $headers);
}

if ($sent) {
    // Only now — after the lead email is confirmed delivered — does this
    // submission consume a coupon slot. See the note in section 6.
    $coupon = allocateCoupon($name, $email, $contact);

    if ($coupon['winner']) {
        // Best-effort supplementary notice; the coupon store file (already
        // written above) is the authoritative record either way.
        @mail(
            RECEIVER_EMAIL,
            "🎁 [Coupon Winner] " . $coupon['code'],
            "Coupon " . $coupon['code'] . " (enquiry #" . $coupon['sequence'] . " of first " . COUPON_WINNER_LIMIT . ") issued to:\n\n"
                . "Name: {$name}\nEmail: {$email}\nContact: {$contact}\n\n"
                . "Full record is in special-offer-coupons.json on the server."
        );

        sendWinnerCongratsEmail($email, $name, $coupon['code'], $coupon['sequence']);
    }

    echo json_encode([
        "success" => true,
        "message" => "Thank you! Your enquiry has been delivered to info@haptags.com. Our team will get back to you shortly with your special-offer proposal.",
        "winner" => $coupon['winner'],
        "coupon_code" => $coupon['code'],
        "sequence" => $coupon['sequence']
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Failed to dispatch email notification. Please email us directly at info@haptags.com or call +91 9986861402.",
        "debug" => (!empty($errorMsg) ? $errorMsg : "PHP mail() returned false. Please verify Hostinger SMTP password.")
    ]);
}
