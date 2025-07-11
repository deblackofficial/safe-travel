<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'includes/PHPMailer/src/Exception.php';
require 'includes/PHPMailer/src/PHPMailer.php';
require 'includes/PHPMailer/src/SMTP.php';

function sendRegistrationEmail($to, $name, $username, $id, $phone) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'emmynzibk21@gmail.com'; 
        $mail->Password   = 'mcxjvyftevdwgvmq';     
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('emmynzibk21@gmail.com', 'Registration System');
        $mail->addAddress($to, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Registration Confirmation';
        $mail->Body    = "
            <h3>Hello $name,</h3>
            <p>You have been successfully registered. Here are your details:</p>
            <ul>
                <li><strong>User ID:</strong> $id</li>
                <li><strong>Username:</strong> $username</li>
                <li><strong>Phone:</strong> $phone</li>
                <li><strong>Email:</strong> $to</li>
            </ul>
            <p>You can now <a href='http://localhost/prj/login.php'>login</a>.</p>
        ";
        $mail->AltBody = "User ID: $id\nUsername: $username\nEmail: $to";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}


?>