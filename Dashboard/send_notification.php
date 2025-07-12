<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../includes/PHPMailer/src/Exception.php';
require '../includes/PHPMailer/src/PHPMailer.php';
require '../includes/PHPMailer/src/SMTP.php';

function sendOverlimitNotification($bus_id, $current_passengers, $max_capacity) {
    include 'conn.php';
    
    error_log("📧 Starting over-limit notification process for bus ID: $bus_id");
    
    // 1. Get bus details with error handling
    $bus_query = "SELECT b.plate_number, r.start_point, r.end_point 
                 FROM buses b 
                 LEFT JOIN routes r ON b.route_id = r.id 
                 WHERE b.id = ?";
    $stmt = $conn->prepare($bus_query);
    
    if (!$stmt) {
        error_log("❌ Database prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $bus_id);
    
    if (!$stmt->execute()) {
        error_log("❌ Query execution failed: " . $stmt->error);
        return false;
    }
    
    $bus = $stmt->get_result()->fetch_assoc();
    
    if (!$bus) {
        error_log("❌ No bus found with ID: $bus_id");
        return false;
    }

    error_log("🚌 Bus details retrieved: {$bus['plate_number']} ({$bus['start_point']} to {$bus['end_point']})");

    // 2. Get admin emails with error handling
    $email_query = "SELECT email FROM users WHERE role = 'admin' AND receive_notifications = 1";
    $result = $conn->query($email_query);
    
    if (!$result) {
        error_log("❌ Email query failed: " . $conn->error);
        return false;
    }
    
    $emails = $result->fetch_all(MYSQLI_ASSOC);
    
    if (empty($emails)) {
        error_log("❌ No admin emails found to notify");
        return false;
    }

    error_log("📧 Found " . count($emails) . " admin email(s) to notify");

    // 3. Configure PHPMailer with enhanced logging
    $mail = new PHPMailer(true);
    
    try {
        // Enable verbose debug output only in development
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer Debug: $str");
            };
        }

        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ericblcdusx@gmail.com'; // Use environment variable in production!
        $mail->Password   = 'zxndjlwqcpkdedcq'; // Generate new one!
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->Timeout    = 60; // Increase timeout
        
        error_log("📧 SMTP settings configured");
        
        // Validate recipient emails
        $validRecipients = 0;
        foreach ($emails as $email) {
            if (filter_var($email['email'], FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($email['email']);
                $validRecipients++;
                error_log("📧 Added recipient: " . $email['email']);
            } else {
                error_log("❌ Invalid email address: " . $email['email']);
            }
        }
        
        if ($validRecipients === 0) {
            error_log("❌ No valid email recipients found");
            return false;
        }

        // Content
        $mail->setFrom('ericblcdusx@gmail.com', 'Safe Travel System');
        $mail->isHTML(true);
        $mail->Subject = '⚠️ Bus Over Capacity Alert - ' . $bus['plate_number'];
        
        // Enhanced HTML email body
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
                .container { background-color: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                .header { background-color: #d32f2f; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { padding: 20px; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f5f5f5; font-weight: bold; }
                .alert { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 4px; margin: 15px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>⚠️ Bus Over Capacity Alert</h2>
                </div>
                <div class='content'>
                    <div class='alert'>
                        <strong>Immediate Action Required:</strong> The following bus has exceeded its passenger capacity and requires immediate attention.
                    </div>
                    
                    <table>
                        <tr><th>Bus Plate Number</th><td><strong>{$bus['plate_number']}</strong></td></tr>
                        <tr><th>Route</th><td>{$bus['start_point']} → {$bus['end_point']}</td></tr>
                        <tr><th>Current Passengers</th><td style='color: #d32f2f; font-weight: bold;'>{$current_passengers}</td></tr>
                        <tr><th>Maximum Capacity</th><td>{$max_capacity}</td></tr>
                        <tr><th>Excess Passengers</th><td style='color: #d32f2f; font-weight: bold;'>" . ($current_passengers - $max_capacity) . "</td></tr>
                        <tr><th>Alert Time</th><td>" . date('Y-m-d H:i:s') . "</td></tr>
                    </table>
                    
                    <p><strong>Please take appropriate action immediately to ensure passenger safety and compliance.</strong></p>
                </div>
                <div class='footer'>
                    <p><em>Safe Travel System - Automated Notification</em></p>
                </div>
            </div>
        </body>
        </html>";
        
        // Plain text version for non-HTML clients
        $mail->AltBody = "URGENT: Bus Over Capacity Alert\n\n" .
                        "Bus Plate: {$bus['plate_number']}\n" .
                        "Route: {$bus['start_point']} to {$bus['end_point']}\n" .
                        "Current Passengers: {$current_passengers}\n" .
                        "Max Capacity: {$max_capacity}\n" .
                        "Excess Passengers: " . ($current_passengers - $max_capacity) . "\n" .
                        "Alert Time: " . date('Y-m-d H:i:s') . "\n\n" .
                        "Please take immediate action to ensure passenger safety.\n\n" .
                        "Safe Travel System - Automated Notification";

        error_log("📧 Attempting to send email to $validRecipients recipient(s)...");
        
        $mail->send();
        
        error_log("✅ Over-limit notification email sent successfully to $validRecipients recipients for bus {$bus['plate_number']}");
        error_log("📊 Capacity Details: {$current_passengers}/{$max_capacity} passengers (+" . ($current_passengers - $max_capacity) . " over limit)");
        
        return true;
        
    } catch (Exception $e) {
        error_log("❌ Email sending failed for bus {$bus['plate_number']}: " . $e->getMessage());
        error_log("❌ PHPMailer Error Info: " . $mail->ErrorInfo);
        
        // Try to get more specific SMTP error
        if ($mail->isConnected()) {
            error_log("❌ SMTP Connection: Connected but failed to send");
        } else {
            error_log("❌ SMTP Connection: Failed to connect to server");
        }
        
        return false;
    }
}

// Function to test email configuration
function testEmailConfiguration() {
    error_log("🧪 Testing email configuration...");
    
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ericblcdusx@gmail.com';
        $mail->Password = 'zxndjlwqcpkdedcq';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 30;
        
        // Try to connect
        if ($mail->smtpConnect()) {
            error_log("✅ Email configuration test: SMTP connection successful");
            $mail->smtpClose();
            return true;
        } else {
            error_log("❌ Email configuration test: SMTP connection failed");
            return false;
        }
    } catch (Exception $e) {
        error_log("❌ Email configuration test failed: " . $e->getMessage());
        return false;
    }
}
?>