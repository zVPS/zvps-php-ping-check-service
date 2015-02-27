<?php

/**
 * LoggerAppenderMailEvent appends individual log events via email.
 * 
 * This appender is similar to LoggerAppenderMail, except that it sends each 
 * each log event in an individual email message at the time when it occurs.
 * 
 * This appender uses a layout.
 * 
 * ## Configurable parameters: ##
 * 
 * - **to** - Email address(es) to which the log will be sent. Multiple email
 *     addresses may be specified by separating them with a comma.
 * - **from** - Email address which will be used in the From field.
 * - **subject** - Subject of the email message.
 * - **smtpHost** - Used to override the SMTP server.
 * - **smtpUser** - Used to authenticate with SMTP server
 * - **smtpPass** - Used to authenticate with SMTP server
 * - **port** - Used to override the default SMTP server port.
 *
 */
class LoggerAppenderSMTPMailEvent extends LoggerAppender {

    /**
     * Email address to put in From field of the email.
     * @var string
     */
    protected $from;

    /**
     * Mail server port (widnows only).
     * @var integer 
     */
    protected $port = 25;

    /**
     * Mail server hostname (windows only).
     * @var string   
     */
    protected $smtpHost;
    /** smtp, login, plain, crammd5 */
    protected $smtpType;
    protected $smtpUser;
    protected $smtpPass;
    protected $smtpTls = false;

    /**
     * The subject of the email.
     * @var string
     */
    protected $subject = 'Log4php Report';

    /**
     * One or more comma separated email addresses to which to send the email. 
     * @var string
     */
    protected $to = null;

    /**
     * Indiciates whether this appender should run in dry mode.
     * @deprecated
     * @var boolean 
     */
    protected $dry = false;

    public function activateOptions() {
        if (empty($this->to)) {
            $this->warn("Required parameter 'to' not set. Closing appender.");
            $this->close = true;
            return;
        }

        $sendmail_from = ini_get('sendmail_from');
        if (empty($this->from) and empty($sendmail_from)) {
            $this->warn("Required parameter 'from' not set. Closing appender.");
            $this->close = true;
            return;
        }

        $this->closed = false;
    }

    public function append(LoggerLoggingEvent $event) {

        $smtpOptions = new \Zend\Mail\Transport\SmtpOptions();
        $smtpOptions->setHost($this->getSmtpHost());
        $smtpOptions->setPort($this->getPort());
        $smtpOptions->setConnectionClass($this->getType());
        
        $smtpOptionsArray = array();
        
        if(strlen($this->getUser()) > 0) { $smtpOptionsArray['username'] = $this->getUser(); }
        if(strlen($this->getPass()) > 0) { $smtpOptionsArray['password'] = $this->getPass(); }
        if(strlen($this->getSmtpHost()) > 0) { $smtpOptionsArray['host'] = $this->getSmtpHost(); }
        if($this->getTls() === true)     { $smtpOptionsArray['ssl'] = 'tls'; }
        if(strlen($this->getUser()) > 0) { $smtpOptionsArray['username'] = $this->getUser(); }
        
        $smtpOptions->setConnectionConfig($smtpOptionsArray);

        $message = new \Zend\Mail\Message();
        $message->setBody($this->layout->getHeader() . $this->layout->format($event) . $this->layout->getFooter($event));
        $message->setFrom($this->getFrom());

        foreach ($this->getTo() as $to) {
            $message->addTo($to);
        }
        $message->setSubject($this->getSubject());

        if (!$this->dry) {
            try {
                $transport = new \Zend\Mail\Transport\Smtp($smtpOptions);
                $transport->send($message);
            } catch (Exception $ex) {
                echo "Sending email alert failed with the following error: " . $ex->getMessage() . PHP_EOL;
            }
            
        } else {
            echo "DRY MODE OF MAIL APP.: Send mail to: " . $this->to . "' with content: " . $this->layout->format($event);
        }
    }

    /** Sets the 'from' parameter. */
    public function setFrom($from) {
        $this->setString('from', $from);
    }

    /** Returns the 'from' parameter. */
    public function getFrom() {
        return $this->from;
    }
    
    public function getUser() {
        return $this->smtpUser;
    }
    public function setUser($smtpUser) {
        $this->setString('smtpUser', $smtpUser);
    }
    public function getPass() {
        return $this->smtpPass;
    }
    public function setPass($smtpPass) {
        $this->setString('smtpPass', $smtpPass);
    }
    public function getType() {
        return $this->smtpType;
    }
    public function setType($smtpType) {
        $this->setString('smtpType', $smtpType);
    }
    public function getTls() {
        return $this->smtpTls;
    }
    /**
     * @param boolean $tls
     */
    public function setTls($tls) {
        $this->setBoolean('smtpTls', $tls);
    }

    /** Sets the 'port' parameter. */
    public function setPort($port) {
        $this->setPositiveInteger('port', $port);
    }

    /** Returns the 'port' parameter. */
    public function getPort() {
        return $this->port;
    }

    /** Sets the 'smtpHost' parameter. */
    public function setSmtpHost($smtpHost) {
        $this->setString('smtpHost', $smtpHost);
    }

    /** Returns the 'smtpHost' parameter. */
    public function getSmtpHost() {
        return $this->smtpHost;
    }

    /** Sets the 'subject' parameter. */
    public function setSubject($subject) {
        $this->setString('subject', $subject);
    }

    /** Returns the 'subject' parameter. */
    public function getSubject() {
        return $this->subject;
    }

    /** Sets the 'to' parameter. */
    public function setTo($to) {
        $this->to = $to;
    }

    /** Returns the 'to' parameter. */
    public function getTo() {
        return $this->to;
    }

    /** Enables or disables dry mode. */
    public function setDry($dry) {
        $this->setBoolean('dry', $dry);
    }

}
