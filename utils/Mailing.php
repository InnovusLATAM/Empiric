<?php
chdir(__DIR__);
require_once '../vendor/autoload.php';
chdir(__DIR__);
require_once '../config.php';
chdir(__DIR__);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailing
{

    private string $host;
    private int $port;
    private string $account_name;
    private string $address;
    private string $password;
    private PHPMailer $mail;
    private bool $debugMode;
    private string $last_error;

    public function __construct(bool $debugMode)
    {
        global $smtp_settings;
        $this->debugMode = $debugMode;
        $this->host = $smtp_settings['host'];
        $this->port = $smtp_settings['port'];
        $this->account_name = $smtp_settings['account_name'];
        $this->address = $smtp_settings['address'];
        $this->password = $smtp_settings['password'];
        $this->mail = new PHPMailer(true);
        $this->mail->CharSet = "UTF-8";
    }

    public function sendMail(string $to, string $subject, string $body): bool
    {

        try {
            $this->mail->SMTPDebug = ($this->debugMode) ? \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER : \PHPMailer\PHPMailer\SMTP::DEBUG_OFF;
            $this->mail->isSMTP();
            $this->mail->Host = $this->host;
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $this->address;
            $this->mail->Password = $this->password;
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = $this->port;

            $this->mail->setFrom($this->address, $this->account_name);
            $this->mail->addAddress($to);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->send();
            return true;
        } catch (\Exception $exception) {
            $this->last_error = $exception->getMessage();
            return false;
        }

    }

    public function getLastError(): string
    {
        return $this->last_error;
    }

}