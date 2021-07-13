<?php

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


class SendMailHelper {

    private PHPMailer $mail;
    
    function __construct($mail = null)
    {
        if($mail == null)
            $this->mail = new PHPMailer(true);
        else
            $this->mail = $mail;

        $this->config();
    }

    private function config(){
        $mail = $this->mail;

        //Server settings
        $mail->SMTPDebug = 2;//SMTP::DEBUG_SERVER; // Enable verbose debug output
        $mail->isSMTP(); // Send using SMTP
        $mail->Host       = 'smtp.tainan.gov.tw'; // Set the SMTP server to send through
        $mail->Port       = 25; // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
        $mail->SMTPAuth   = true; // Enable SMTP authentication
        $mail->SMTPAutoTLS = false;

        $mail->Username   = '**************'; // SMTP username
        $mail->Password   = '***********'; // SMTP password
        $mail->setFrom($mail->Username, '阿舜');

        $mail->CharSet = 'utf8'; // 設定郵件編碼
        $mail->isHTML(true);// Set email format to HTML
    }

    function get_mail(){
        return $this->mail;
    }
}
