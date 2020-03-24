<?php
/**
 * Created by PhpStorm.
 * User: gk
 * Date: 2019/10/19
 * Time: 2:05
 */

namespace DHelper\Libs;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class SendMailClient
{

    private static $host = 'smtp.163.com';
    private static $port = 587;
    private static $SMTPDebug = SMTP::DEBUG_OFF;
    private static $SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    private static $username = USER_2;
    private static $password = PWD_2;
    private static $language = 'zh_cn';
    private static $exceptions = true;
    private static $isHtml = true;

    public static function sendMail($to = '', $subject = '', $body = '', $option = [])
    {
        try {
            $mail = new PHPMailer(static::$exceptions);
            //Server settings
            $mail->SMTPDebug = $option['debug']??static::$SMTPDebug;                      // Enable verbose debug output
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host = static::$host;                    // Set the SMTP server to send through
            $mail->SMTPAuth = true;                                   // Enable SMTP authentication
            $mail->Username = static::$username;                     // SMTP username
            $mail->Password = static::$password;                               // SMTP password
            $mail->SMTPSecure = static::$SMTPSecure;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
            $mail->Port = static::$port;                                    // TCP port to connect to
            $mail->setLanguage(static::$language);

            //Recipients
            // from
            if(isset($option['from'])){
                if (is_array($option['from'])) {
                    $mail->setFrom(...$option['from']);
                }elseif(is_string($option['from'])){
                    $mail->setFrom($option['from']);
                }else{
                    return false;
                }
            }else{
                $mail->setFrom(static::$username, NICKNAME_1);
            }

            // to
            if (is_array($to)) {
                $mail->addAddress(...$to);
            }elseif(is_string($to)){
                $mail->addAddress($to,'a');
            }else{
                return false;
            }

            if(isset($option['reply_to'])){
                $mail->addReplyTo(...$option['reply_to']);
            }
            if(isset($option['cc'])){
                $mail->addCC(...$option['cc']);
            }
            if(isset($option['bcc'])){
                $mail->addBCC(...$option['bcc']);
            }

            if (isset($option['attachment'])) {
                $mail->addAttachment(...$option['attachment']);         // Add attachments
            }

            // Content
            $mail->isHTML(static::$isHtml);                                  // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body = $body;
//            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
            return $mail->send();
        } catch (PHPMailerException $e) {
            // log
            print_r($e);
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

}