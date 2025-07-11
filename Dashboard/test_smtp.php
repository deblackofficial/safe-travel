<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Enable verbose debug output
    $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Shows full debug output
    $mail->Debugoutput = function($str, $level) {
        file_put_contents('smtp_debug.log', "$level: $str\n", FILE_APPEND);
    };

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'emmynzibk21@gmail.com';
    $mail->Password   = 'zxndjlwqcpkdedcq'; // Replace with new app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('emmynzibk21@gmail.com', 'Test System');
    $mail->addAddress('emmynzibk21@example.com'); // Change to your personal email

    $mail->isHTML(true);
    $mail->Subject = 'SMTP Test Email';
    $mail->Body    = 'This is a test email from your server';

    $mail->send();
    echo 'Email sent successfully! Check your inbox (and spam folder).';
} catch (Exception $e) {
    echo "Error: {$mail->ErrorInfo}";
    file_put_contents('smtp_errors.log', date('Y-m-d H:i:s')." - {$mail->ErrorInfo}\n", FILE_APPEND);
}
?>