<?php

require_once __DIR__ . '/swift/swift_required.php';

class Email {

    static public $last_error_message = '';
    protected $_host = null;
    protected $_port = null;
    protected $_security = null;
    protected $_useAuth = null;
    protected $_username = null;
    protected $_password = null;
    protected $_error = null;

    static public function createInstance() {
        return new Email(EMAIL_HOST, EMAIL_PORT, EMAIL_SECURITY, EMAIL_USE_CREDENTIALS, EMAIL_USERNAME, EMAIL_PASSWORD);
    }

    public function __construct($host = 'localhost', $port = 25, $security = null, $useAuth = false, $username = '', $password = '') {
        $this->_host = $host;
        $this->_port = $port;
        $this->_security = $security;
        $this->_useAuth = $useAuth;
        $this->_username = $username;
        $this->_password = $password;
        $this->_error = null;
    }

    public function getError() {
        return $this->_error;
    }

    /**
     *
     * @param string $subject
     * @param string $body
     * 
     * @param string/array $from Ex: array('email@address.com' => 'Real Name')
     * @param string/array $to Ex: array('email@address.com' => 'Real Name')
     * @param string/array $cc Ex: array('email@address.com' => 'Real Name')
     * @param string/array $bcc Ex: array('email@address.com' => 'Real Name')
     * @param string/array $replyTo Ex: array('email@address.com' => 'Real Name')
     * 
     * @param string $contentType
     * @param string $charset
     * @return boolean 
     */
    public function send($subject, $body, $from, $to, $cc = null, $bcc = null, $replyTo = null, $contentType = 'text/html', $charset = 'utf-8') {

        if (!USE_EMAIL) {
            self::$last_error_message = 'ERROR: USE_EMAIL must be set to TRUE to use email functions';
            return null;
        }

        try {
            // Handle the mail transport
            $transport = Swift_SmtpTransport::newInstance($this->_host, $this->_port, $this->_security);
            if ($this->_useAuth !== false) {
                $transport->setUsername($this->_username);
                $transport->setPassword($this->_password);
            }

            // Handle the mail sender
            $mailer = Swift_Mailer::newInstance($transport);

            // Create the message
            $message = Swift_Message::newInstance($subject, $body, $contentType, $charset);
            $message->setFrom($from);
            $message->setTo($this->getEmailAddresses($to));

            if (count($from) > 1) {
                // If there are more than one sender (from), must setSender in swift lib
                // This sets the first sender to be the oficial one
                $values = $from;
                $keys = array_keys($from);
                $message->setSender($keys[0], $values[0]);
            }

            $normalized_cc = $this->getEmailAddresses($cc);
            if (!empty($normalized_cc)) {
                $message->setCc($normalized_cc);
            }

            $normalized_bcc = $this->getEmailAddresses($bcc);
            if (!empty($normalized_bcc)) {
                $message->setBcc($normalized_bcc);
            }

            $normalized_replyTo = $this->getEmailAddresses($replyTo);
            if (!empty($normalized_replyTo)) {
                $message->setReplyTo($normalized_replyTo);
            }

            // Send mail
            $result = $mailer->send($message);
            if ($result) {
                return true;
            } else {
                $message = 'Internal send function returned a false value: ' . $result;
                $this->_error = $message;
                self::$last_error_message = $message;
                return false;
            }
        } catch (Exception $e) {
            $this->_error = $e;
            self::$last_error_message = 'Occurred an exception when sending mail. Exception: ' . $e->getMessage();
            return false;
        }

        $message = 'Unknown error sending mail';
        $this->_error = $message;
        self::$last_error_message = $message;
        return false;
    }

    protected function getEmailAddresses($email_adresses) {
        $result = array();

        $addresses = explode(',', $email_adresses);
        foreach ($addresses as $address) {
            $address = trim($address);
            if ($address != '') {
                $result[$address] = $address;
            }
        }

        return $result;
    }

}

?>