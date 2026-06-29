<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendLeadNotification(array $submission): bool {
    $config = require __DIR__ . '/../../config/config.php';
    $mail = $config['mail'] ?? null;
    if (!$mail || empty($mail['password'])) {
        return false;
    }

    $mailer = new PHPMailer(true);
    try {
        $mailer->isSMTP();
        $mailer->Host = $mail['host'];
        $mailer->Port = $mail['port'];
        $mailer->SMTPAuth = true;
        $mailer->Username = $mail['username'];
        $mailer->Password = $mail['password'];
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->CharSet = 'UTF-8';

        $mailer->setFrom($mail['from_email'], $mail['from_name']);
        $mailer->addAddress($mail['to_email']);
        if (!empty($submission['email'])) {
            $mailer->addReplyTo($submission['email'], $submission['name'] ?? '');
        }

        $isPartial = ($submission['lead_type'] ?? 'full') === 'partial';
        $mailer->Subject = ($isPartial ? '[Partial Lead] ' : '[New Lead] ') . 'TFT Deutschland website submission';

        $mailer->isHTML(true);
        $mailer->Body = buildLeadEmailHtml($submission, $isPartial);

        $mailer->send();
        return true;
    } catch (Exception $e) {
        error_log('sendLeadNotification failed: ' . $mailer->ErrorInfo);
        return false;
    }
}

function buildLeadEmailHtml(array $submission, bool $isPartial): string {
    $rows = '';
    $fields = [
        'name' => 'Full Name',
        'email' => 'Email',
        'company' => 'Company',
        'message' => 'Message',
    ];
    $i = 0;
    foreach ($fields as $key => $label) {
        if (empty($submission[$key])) {
            continue;
        }
        $bg = $i % 2 === 0 ? '#f9fafb' : '#ffffff';
        $value = htmlspecialchars($submission[$key]);
        if ($key === 'email') {
            $value = '<a href="mailto:' . $value . '" style="color:#2563eb;text-decoration:underline;">' . $value . '</a>';
        }
        $rows .= '
        <tr>
          <td style="padding:14px 20px;background:' . $bg . ';font-weight:600;color:#1f2937;width:160px;border-bottom:1px solid #eceff3;">' . $label . ':</td>
          <td style="padding:14px 20px;background:' . $bg . ';color:#374151;border-bottom:1px solid #eceff3;">' . $value . '</td>
        </tr>';
        $i++;
    }

    $heading = $isPartial ? 'Germany : New Partial Lead Captured' : 'Germany : New Contact Request Details';
    $title = 'Contact Query - Think Future Technologies';

    return '
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;font-family:Arial,Helvetica,sans-serif;">
      <tr>
        <td align="center">
          <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
            <tr>
              <td style="background:#2f7de1;padding:28px 24px;text-align:center;">
                <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:700;">' . $title . '</h1>
              </td>
            </tr>
            <tr>
              <td style="padding:24px 24px 4px;">
                <h2 style="margin:0 0 16px;font-size:16px;color:#2f7de1;font-weight:700;">' . $heading . '</h2>
              </td>
            </tr>
            <tr>
              <td style="padding:0 24px 8px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eceff3;border-radius:6px;overflow:hidden;">' . $rows . '
                </table>
              </td>
            </tr>
            <tr>
              <td style="padding:18px 24px 28px;color:#9ca3af;font-size:12px;">
                This email was automatically generated from your website contact form.
              </td>
            </tr>
            <tr>
              <td style="padding:16px 24px;background:#f9fafb;text-align:center;color:#9ca3af;font-size:11px;border-top:1px solid #eceff3;">
                &copy; ' . date('Y') . ' Think Future Technologies. All rights reserved.
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>';
}
