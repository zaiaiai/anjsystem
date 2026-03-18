<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

function sendVerificationEmail($toEmail, $toName, $code) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dabecerina@gmail.com';   // <-- Replace with clinic Gmail
        $mail->Password   = 'wllm bhmo fdjo vjyk';        // <-- Replace with Gmail App Password (16 chars)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('your_clinic_email@gmail.com', 'Dental Clinic System');
        $mail->addAddress($toEmail, $toName);

        $mail->Subject = 'Your Verification Code — Dental Clinic System';
        $mail->isHTML(true);
        $mail->Body = "
            <div style='font-family:Arial,sans-serif; max-width:520px;
                        margin:auto; border:1px solid #ddd;
                        border-radius:10px; padding:36px;'>
                <h2 style='color:#1a6fb5; text-align:center; margin-bottom:4px;'>
                    🦷 Dental Clinic Management System
                </h2>
                <p style='text-align:center; color:#888; font-size:13px; margin-top:0;'>
                    Staff Account Verification
                </p>
                <hr style='border:none; border-top:2px solid #1a6fb5; margin:20px 0;'>
                <p>Hello <strong>{$toName}</strong>,</p>
                <p>Your account has been created by the clinic owner.
                   To activate it, please enter the verification code below:</p>
                <div style='text-align:center; margin:30px 0;'>
                    <span style='font-size:42px; font-weight:bold;
                                 letter-spacing:14px; color:#1a6fb5;
                                 background:#e8f3fc; padding:16px 30px;
                                 border-radius:8px; display:inline-block;'>
                        {$code}
                    </span>
                </div>
                <p>Enter this 5-digit code on the verification page to activate your account.</p>
                <p>After verifying, you will be taken to the login page to sign in.</p>
                <p style='color:#888; font-size:12px;'>
                    If you did not expect this email, please contact your clinic administrator.
                </p>
                <hr style='border:none; border-top:1px solid #ddd; margin:20px 0;'>
                <p style='text-align:center; color:#aaa; font-size:11px;'>
                    Dental Clinic Management System &mdash; Staff Portal
                </p>
            </div>
        ";

        $mail->AltBody = "Hello {$toName},\n\n"
                       . "Your 5-digit verification code is: {$code}\n\n"
                       . "Enter this code on the verify.php page.\n"
                       . "After verifying, go to login.php to sign in.\n\n"
                       . "Dental Clinic Management System";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('Mail error: ' . $mail->ErrorInfo);
        return false;
    }
}
?>