<?php
/**
 * HAPTAGS LLP — Consultation & Lead Mailer
 * Target Destination: info@haptags.com
 * SMTP Host: smtp.hostinger.com
 * 
 * Instructions:
 * 1. Place this file in your website root directory on Hostinger.
 * 2. Update SMTP_PASS below with your info@haptags.com email password.
 * 3. When visitors submit the consultation form, emails will be securely sent to info@haptags.com.
 */

// Enable CORS if needed (for cross-origin AJAX during development)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// -----------------------------------------------------------------------------
// 1. CONFIGURATION (Hostinger Mail Settings)
// -----------------------------------------------------------------------------
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465); // 465 for SSL, 587 for TLS
define('SMTP_SECURE', 'ssl'); // 'ssl' or 'tls'
define('SMTP_USER', 'info@haptags.com');
define('SMTP_PASS', 'YOUR_HOSTINGER_EMAIL_PASSWORD'); // <-- ENTER YOUR HOSTINGER EMAIL PASSWORD HERE

define('RECEIVER_EMAIL', 'info@haptags.com');
define('RECEIVER_NAME', 'Haptags LLP Inquiries');
define('SENDER_EMAIL', 'info@haptags.com');
define('SENDER_NAME', 'Haptags Website Portal');

// -----------------------------------------------------------------------------
// 2. HELPER FUNCTIONS & SMTP SENDER
// -----------------------------------------------------------------------------
class SimpleHostingerSMTP {
    private $host;
    private $port;
    private $secure;
    private $username;
    private $password;
    private $socket = null;
    private $logs = [];

    public function __construct($host, $port, $secure, $username, $password) {
        $this->host = $host;
        $this->port = $port;
        $this->secure = strtolower($secure);
        $this->username = $username;
        $this->password = $password;
    }

    private function log($msg) {
        $this->logs[] = $msg;
    }

    public function getLogs() {
        return $this->logs;
    }

    private function readResponse() {
        $response = "";
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == " ") {
                break;
            }
        }
        $this->log("SERVER: " . trim($response));
        return $response;
    }

    private function sendCommand($cmd, $expectedCode) {
        $this->log("CLIENT: " . (strpos($cmd, 'AUTH') === false && !base64_decode($cmd, true) ? $cmd : '***'));
        fputs($this->socket, $cmd . "\r\n");
        $response = $this->readResponse();
        $code = substr($response, 0, 3);
        if ($code != $expectedCode) {
            throw new Exception("SMTP Error: Expected $expectedCode but got $response");
        }
        return $response;
    }

    public function send($to, $toName, $from, $fromName, $replyTo, $subject, $htmlBody, $plainBody) {
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

        // Authenticate
        $this->sendCommand("AUTH LOGIN", 334);
        $this->sendCommand(base64_encode($this->username), 334);
        $this->sendCommand(base64_encode($this->password), 235);

        // Sender & Recipient
        $this->sendCommand("MAIL FROM: <{$from}>", 250);
        $this->sendCommand("RCPT TO: <{$to}>", 250);

        // Data
        $this->sendCommand("DATA", 354);

        $boundary = "----=_NextPart_" . md5(time() . rand());

        $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n";
        $headers .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$to}>\r\n";
        if (!empty($replyTo)) {
            $headers .= "Reply-To: <{$replyTo}>\r\n";
        }
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "X-Mailer: Haptags LLP Web Mailer\r\n";

        $message  = $headers . "\r\n";
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($plainBody)) . "\r\n";

        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $message .= "--{$boundary}--\r\n";
        $message .= ".";

        $this->sendCommand($message, 250);
        $this->sendCommand("QUIT", 221);

        fclose($this->socket);
        return true;
    }
}

// -----------------------------------------------------------------------------
// 3. MAIN REQUEST HANDLER
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Method not allowed. Only POST requests are accepted."]);
    exit;
}

// Support both form-data/url-encoded and raw JSON body
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);
$data = (!empty($_POST)) ? $_POST : (is_array($jsonData) ? $jsonData : []);

// Honeypot spam protection
if (!empty($data['_gotcha'])) {
    // Silent fail for bots
    echo json_encode(["success" => true, "message" => "Inquiry received."]);
    exit;
}

$formType = isset($data['form_type']) ? trim($data['form_type']) : 'consultation';
$name     = isset($data['name']) ? trim(strip_tags($data['name'])) : '';
$email    = isset($data['email']) ? trim(filter_var($data['email'], FILTER_SANITIZE_EMAIL)) : '';
$phone    = isset($data['phone']) ? trim(strip_tags($data['phone'])) : '';
$service  = isset($data['service']) ? trim(strip_tags($data['service'])) : 'General Consultation';
$message  = isset($data['message']) ? trim(strip_tags($data['message'])) : '';

// Validation
if (empty($name) || empty($email)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Name and Email are required fields."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Invalid email address format."]);
    exit;
}

// Map service codes to friendly names
$serviceMap = [
    'webdev'     => 'Website Development',
    'appdev'     => 'Mobile App Development (iOS & Android)',
    'marketing'  => 'Digital & Social Media Marketing',
    'interior'   => 'Luxury Interior Design & Solutions',
    'realestate' => 'Real Estate Agency & Advisory',
    'all'        => 'Multiple Services / Unified Project'
];
$serviceFormatted = isset($serviceMap[$service]) ? $serviceMap[$service] : htmlspecialchars($service);

$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
$timestamp = date('l, F j, Y \a\t g:i A \I\S\T');

// -----------------------------------------------------------------------------
// 4. EMAIL TEMPLATES
// -----------------------------------------------------------------------------
if ($formType === 'brochure') {
    $subject = "📄 [Brochure Request] {$name} requested Corporate Deck";
    $heading = "New Corporate Profile Download Request";
} else {
    $subject = "✨ [New Consultation Lead] {$name} - {$serviceFormatted}";
    $heading = "New Direct Consultation Request";
}

// Clean HTML Template
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
    .email-badge { display: inline-block; background: #6366f1; color: #ffffff; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 4px; margin-left: 6px; }
    .email-subhead { color: #94a3b8; font-size: 13px; margin-top: 6px; }
    .email-body { padding: 32px 28px; }
    .alert-banner { background: rgba(99, 102, 241, 0.1); border-left: 4px solid #6366f1; padding: 14px 18px; border-radius: 6px; margin-bottom: 24px; color: #c7d2fe; font-size: 14px; }
    .data-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .data-table td { padding: 12px 14px; border-bottom: 1px solid #1f293d; font-size: 14px; }
    .data-table td.label { width: 35%; color: #94a3b8; font-weight: 600; vertical-align: top; }
    .data-table td.value { color: #f8fafc; font-weight: 500; }
    .service-tag { display: inline-block; background: #1e293b; color: #38bdf8; border: 1px solid #0284c7; padding: 4px 12px; border-radius: 20px; font-weight: 600; font-size: 13px; }
    .message-box { background-color: #0d131f; border: 1px solid #1e293b; border-radius: 8px; padding: 18px; color: #cbd5e1; font-size: 14px; line-height: 1.6; white-space: pre-wrap; margin-top: 8px; }
    .btn-action { display: inline-block; background: #4f46e5; color: #ffffff !important; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; margin-top: 15px; }
    .email-footer { background-color: #0b0f17; padding: 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #1f293d; }
  </style>
</head>
<body>
  <div class='email-container'>
    <div class='email-header'>
      <div class='email-logo'>HAPTAGS <span class='email-badge'>LLP</span></div>
      <div class='email-subhead'>Integrated Web, App, Marketing, Interior & Real Estate Strategy</div>
      <h2 style='color:#ffffff; margin: 16px 0 0 0; font-size: 20px;'>{$heading}</h2>
    </div>

    <div class='email-body'>
      <div class='alert-banner'>
        ⚡ <strong>New Lead Notification:</strong> A prospective client has submitted an inquiry via <strong>haptags.com</strong>.
      </div>

      <table class='data-table'>
        <tr>
          <td class='label'>Client / Organization:</td>
          <td class='value' style='font-size:16px; font-weight:700; color:#ffffff;'>" . htmlspecialchars($name) . "</td>
        </tr>
        <tr>
          <td class='label'>Email Address:</td>
          <td class='value'>
            <a href='mailto:" . htmlspecialchars($email) . "' style='color:#38bdf8; text-decoration:none; font-weight:600;'>" . htmlspecialchars($email) . "</a>
          </td>
        </tr>
        <tr>
          <td class='label'>Phone / WhatsApp:</td>
          <td class='value'>
            <a href='tel:" . htmlspecialchars($phone) . "' style='color:#34d399; text-decoration:none; font-weight:600;'>" . (!empty($phone) ? htmlspecialchars($phone) : 'Not provided') . "</a>
          </td>
        </tr>
        <tr>
          <td class='label'>Primary Service:</td>
          <td class='value'>
            <span class='service-tag'>" . $serviceFormatted . "</span>
          </td>
        </tr>
        <tr>
          <td class='label'>Submission Time:</td>
          <td class='value' style='color:#94a3b8; font-size:13px;'>" . $timestamp . "</td>
        </tr>
      </table>

      " . (!empty($message) ? "
      <div style='margin-top: 15px;'>
        <div style='color: #94a3b8; font-weight: 600; font-size: 13px; margin-bottom: 6px;'>PROJECT GOALS / MESSAGE:</div>
        <div class='message-box'>" . nl2br(htmlspecialchars($message)) . "</div>
      </div>
      " : "") . "

      <div style='text-align: center; margin-top: 28px;'>
        <a href='mailto:" . htmlspecialchars($email) . "?subject=" . urlencode("Re: Your Consultation Request with Haptags LLP") . "' class='btn-action'>
          ✉️ Reply to " . htmlspecialchars($name) . "
        </a>
      </div>
    </div>

    <div class='email-footer'>
      <strong>Haptags LLP</strong> &bull; Near Maheshwarramma temple road, Mahadevapura, Bangalore - 560048<br>
      Automated dispatch from Hostinger SMTP (<a href='https://haptags.com' style='color:#6366f1; text-decoration:none;'>haptags.com</a>) &bull; Client IP: " . htmlspecialchars($clientIP) . "
    </div>
  </div>
</body>
</html>
";

// Plain Text Alternative
$plainBody = "
============================================================
HAPTAGS LLP — NEW DIRECT CONSULTATION INQUIRY
============================================================

Client Name: {$name}
Email Address: {$email}
Phone / WhatsApp: {$phone}
Primary Service: {$serviceFormatted}
Submitted At: {$timestamp}
Client IP: {$clientIP}

PROJECT DETAILS / REQUIREMENTS:
------------------------------------------------------------
" . (!empty($message) ? $message : "No additional notes provided.") . "
------------------------------------------------------------

Haptags LLP — info@haptags.com
Near Maheshwarramma temple road, Mahadevapura, Bangalore - 560048
";

// -----------------------------------------------------------------------------
// 5. SEND EMAIL VIA HOSTINGER SMTP OR FALLBACK
// -----------------------------------------------------------------------------
$sent = false;
$errorMsg = "";

// Check if SMTP password has been configured
if (defined('SMTP_PASS') && SMTP_PASS !== 'YOUR_HOSTINGER_EMAIL_PASSWORD' && !empty(SMTP_PASS)) {
    try {
        $smtp = new SimpleHostingerSMTP(SMTP_HOST, SMTP_PORT, SMTP_SECURE, SMTP_USER, SMTP_PASS);
        $sent = $smtp->send(
            RECEIVER_EMAIL,
            RECEIVER_NAME,
            SENDER_EMAIL,
            SENDER_NAME,
            $email, // Reply-to client email
            $subject,
            $htmlBody,
            $plainBody
        );
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
}

// Fallback to PHP native mail() if SMTP credentials aren't set or if SMTP fails
if (!$sent) {
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
    echo json_encode([
        "success" => true,
        "message" => "Thank you! Your consultation request has been delivered to info@haptags.com. Our partners will reach out to you shortly."
    ]);
} else {
    // If both failed
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Failed to dispatch email notification. Please email us directly at info@haptags.com or call +91 9986861402.",
        "debug" => (!empty($errorMsg) ? $errorMsg : "PHP mail() returned false. Please verify Hostinger SMTP password.")
    ]);
}
