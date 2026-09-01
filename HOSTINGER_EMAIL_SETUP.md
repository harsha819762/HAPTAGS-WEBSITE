# Hostinger Email Configuration Guide for Haptags LLP

The consultation form on **haptags.com** is configured to deliver incoming leads directly to **`info@haptags.com`** using Hostinger's SMTP server.

---

## 📁 Files Created / Modified

1. **[`send-mail.php`](file:///c:/Haptagswebsie1/send-mail.php)** — Standalone PHP backend mailer supporting direct Hostinger SMTP with SSL/TLS and automatic fallback.
2. **[`index.html`](file:///c:/Haptagswebsie1/index.html)** — Form markup updated with proper IDs, field names, autocomplete attributes, and anti-spam protection.
3. **[`js/app.js`](file:///c:/Haptagswebsie1/js/app.js)** — Asynchronous form handler with dynamic loading spinners, interactive feedback, and toast notifications.
4. **[`css/animations.css`](file:///c:/Haptagswebsie1/css/animations.css)** — Spinner animation for real-time button submission state.

---

## ⚙️ Hostinger SMTP Configuration Details

In [`send-mail.php`](file:///c:/Haptagswebsie1/send-mail.php), the email settings are defined as:

```php
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465); // SSL Port
define('SMTP_SECURE', 'ssl');
define('SMTP_USER', 'info@haptags.com');
define('SMTP_PASS', 'YOUR_HOSTINGER_EMAIL_PASSWORD'); // <-- Enter your Hostinger password here

define('RECEIVER_EMAIL', 'info@haptags.com');
define('RECEIVER_NAME', 'Haptags LLP Inquiries');
```

### 1-Step Setup on Hostinger:
1. Open [`send-mail.php`](file:///c:/Haptagswebsie1/send-mail.php).
2. Replace `'YOUR_HOSTINGER_EMAIL_PASSWORD'` with your actual email password for `info@haptags.com` created in Hostinger Webmail / hPanel.
3. Upload your website files (`index.html`, `send-mail.php`, `css/`, `js/`, `assets/`) to Hostinger's `public_html` directory.

---

## 📬 Receiving & Checking Inquiries (IMAP Settings)

When clients submit the consultation form, the email lands in your `info@haptags.com` inbox. You can check it via Hostinger Webmail or configure Outlook / Apple Mail / Thunderbird using:

- **IMAP Host**: `imap.hostinger.com`
- **IMAP Port**: `993` (SSL/TLS)
- **SMTP Host**: `smtp.hostinger.com`
- **SMTP Port**: `465` (SSL) or `587` (TLS)
- **Email**: `info@haptags.com`
