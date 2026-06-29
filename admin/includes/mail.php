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

        $body = '<h2>' . ($isPartial ? 'Partial lead captured' : 'New form submission') . '</h2><ul>';
        foreach (['name' => 'Name', 'email' => 'Email', 'company' => 'Company', 'message' => 'Message'] as $key => $label) {
            if (!empty($submission[$key])) {
                $body .= '<li><strong>' . $label . ':</strong> ' . htmlspecialchars($submission[$key]) . '</li>';
            }
        }
        $body .= '</ul>';
        $mailer->isHTML(true);
        $mailer->Body = $body;

        $mailer->send();
        return true;
    } catch (Exception $e) {
        error_log('sendLeadNotification failed: ' . $mailer->ErrorInfo);
        return false;
    }
}
