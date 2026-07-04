<?php
// send_otp.php
require_once __DIR__ . '/phpmailer/PHPMailerAutoload.php';

function sendOTP($email, $otp) {
    $mail = new PHPMailer;
    
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 587;
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'tls';
    $mail->Username = 'azzarthfatly@gmail.com';
    $mail->Password = 'xozw kfbc ntek dndk';
    
    $mail->setFrom('azzarthfatly@gmail.com', 'CHILLCOM SYSTEM');
    $mail->addAddress($email);
    $mail->isHTML(true);
    
    $subject = 'Password Reset Verification - CHILLCOM';
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; background: #f5f5f5; border-radius: 10px; overflow: hidden; }
            .header { background: linear-gradient(135deg, #7289da, #4a5fa8); color: white; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { background: white; padding: 30px; }
            .code-box { background: #f0f0f0; padding: 25px; text-align: center; font-size: 36px; font-weight: bold; letter-spacing: 5px; color: #7289da; border-radius: 10px; margin: 20px 0; border: 2px dashed #7289da; font-family: monospace; }
            .footer { background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>CHILLCOM SYSTEM</h1>
                <p>Password Reset Verification</p>
            </div>
            <div class='content'>
                <h3>Password Reset Request</h3>
                <p>You requested to reset your password. Use the OTP code below:</p>
                <div class='code-box'>{$otp}</div>
                <p><strong>This OTP is valid for 10 minutes.</strong></p>
                <p>If you didn't request this, please ignore this email.</p>
                <p>Best regards,<br>The ChillCom Team</p>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " ChillCom Minecraft Community. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody = "Your OTP code for password reset is: $otp\n\nValid for 10 minutes.\n\nCHILLCOM MINECRAFT";
    
    return $mail->send();
}
?>