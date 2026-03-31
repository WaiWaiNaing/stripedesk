<?php

namespace Stripedesk\Mail;

/**
 * Sends OTP codes via SMTP (when MAIL_HOST is set) or PHP mail().
 */
final class Otp_mailer
{
    /**
     * @param string $to_email
     * @param string $otp_plain  6-digit code
     * @param string $intent     registration|account_activation|password_reset
     * @return bool              true if send attempted without exception (SMTP/mail)
     */
    public function send($to_email, $otp_plain, $intent)
    {
        if ( ! function_exists('sd_env'))
        {
            return false;
        }
        $to_email = trim((string) $to_email);
        if ($to_email === '' || $otp_plain === '')
        {
            return false;
        }

        $from = sd_env('MAIL_FROM_ADDRESS', sd_env('MAIL_FROM', 'noreply@localhost'));
        $from_name = sd_env('MAIL_FROM_NAME', 'StripeDesk');
        $subject = 'Your StripeDesk verification code';
        $lines = array(
            'Your verification code is: ' . $otp_plain,
            '',
            'This code expires in a few minutes.',
            '',
            'If you did not request this, you can ignore this email.',
            '',
            'Intent: ' . $intent,
        );
        $body = implode("\n", $lines);

        $host = trim((string) sd_env('MAIL_HOST', ''));
        if ($host !== '' && class_exists(\PHPMailer\PHPMailer\PHPMailer::class))
        {
            return $this->send_smtp($to_email, $subject, $body, $from, $from_name);
        }

        $headers = array(
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: ' . $this->encode_header_name($from_name) . ' <' . $from . '>',
        );
        $ok = @mail($to_email, $subject, $body, implode("\r\n", $headers));

        return $ok;
    }

    private function encode_header_name($name)
    {
        return str_replace(array("\r", "\n"), '', $name);
    }

    private function send_smtp($to_email, $subject, $body, $from, $from_name)
    {
        try
        {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = (string) sd_env('MAIL_HOST', 'localhost');
            $mail->Port = (int) sd_env_int('MAIL_PORT', 587);
            $user = sd_env('MAIL_USERNAME', '');
            $pass = sd_env('MAIL_PASSWORD', '');
            if ($user !== '')
            {
                $mail->SMTPAuth = true;
                $mail->Username = $user;
                $mail->Password = $pass;
            }
            else
            {
                $mail->SMTPAuth = false;
            }
            $enc = strtolower((string) sd_env('MAIL_ENCRYPTION', 'tls'));
            if ($enc === 'tls')
            {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            elseif ($enc === 'ssl')
            {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            }
            elseif ($enc === '')
            {
                $mail->SMTPAutoTLS = false;
            }
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($from, $from_name);
            $mail->addAddress($to_email);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();

            return true;
        }
        catch (\Throwable $e)
        {
            log_message('error', 'Otp_mailer SMTP: ' . $e->getMessage());

            return false;
        }
    }
}
