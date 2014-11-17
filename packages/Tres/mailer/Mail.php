<?php

namespace packages\Tres\mailer {
    
    use Exception;
    
    class MailException extends Exception implements ExceptionInterface {}
    
    class Mail {
        
        /**
         * The email author.
         * 
         * @var string
         */
        public $from = null;
        
        /**
         * The email subject.
         * 
         * @var string
         */
        public $subject = null;
        
        /**
         * The recipient(s).
         * 
         * @var string|array
         */
        public $to = null;
        
        /**
         * The person/people to get a carbon copy.
         * 
         * @var string|array
         */
        public $cc = null;
        
        /**
         * The person/people to get a blind carbon copy.
         * 
         * @var string|array
         */
        public $bcc = null;
        
        /**
         * The message body.
         * 
         * @var string
         */
        public $body = '';
        
        /**
         * The character set.
         * 
         * @var string
         */
        public $charset = 'UTF-8';
        
        /**
         * The recipients (to, cc and bcc).
         * 
         * @var array
         */
        protected $_recipients = [];
        
        /**
         * The content type.
         * 
         * @var string
         */
        protected $_contentType = '';
        
        /**
         * The headers.
         * 
         * @var array
         */
        protected $_headers = [];
        
        /**
         * The mail configuration.
         * 
         * @var array
         */
        protected $_config = [];
        
        /**
         * The mail connection.
         * 
         * @var \packages\Tres\mailer\Connection
         */
        protected $_connection = null;
        
        /**
         * The SMTP conversation log.
         * 
         * @var \packages\Tres\mailer\MailLog
         */
        protected $_conversationLog = null;
        
        /**
         * The address separator.
         */
        const ADDRESS_SEPARATOR = ', ';
        
        /**
         * The MIME-Version.
         */
        const MIME_VERSION = '1.0';
        
        /**
         * Initializes mailer.
         * 
         * @param \packages\Tres\mailer\Connection $conn The connection.
         */
        public function __construct(Connection $conn = null){
            $this->_config = Config::get();
            
            if(empty($this->_config)){
                throw new MailException('Mail configuration not set.');
            }
            
            if(empty($conn)){
                $conn = new Connection($this->_config['defaults']['connection']);
            }
            
            $this->_connection = $conn;
            $this->_conversationLog = $conn->getLog();
        }
        
        /**
         * Changes the content type.
         */
        public function isHTML(){
            $this->_contentType = 'text/html; charset='.$this->charset;
        }
        
        /**
         * Adds a header.
         * 
         * @param string $name  The header name.
         * @param string $value The header value.
         */
        public function addHeader($name, $value){
            // TODO: secure against header injection
            $this->_headers[$name] = $value;
        }
        
        /**
         * Processes the email.
         * 
         * @return bool
         */
        public function send(){
            $this->_preSend();
            
            $from = $this->_getEmail($this->from);
            $this->_conversationLog->add('MAIL FROM', $this->_connection->sendCommand('MAIL FROM: <'.$from.'>'));
            
            foreach($this->_recipients as $recipient){
                $recipient = $this->_getEmail($recipient);
                
                $this->_conversationLog->add('RCPT TO', $this->_connection->sendCommand('RCPT TO: <'.$recipient.'>'));
            }
            
            $this->_conversationLog->add('DATA', $this->_connection->sendCommand('DATA'));
            
            $data = $this->_connection->sendCommand($this->_getHeaders().$this->_getMessage());
            
            $this->_conversationLog->add('DATA', $data);
            $this->_conversationLog->add('QUIT', $this->_connection->sendCommand('QUIT'));
            
            $this->_connection->close();
            
            return substr($data, 0, 3) == 250;
        }
        
        /**
         * Displays the conversation log.
         * 
         * @return array
         */
        public function displayLog($return = false){
            if($return){
                return $this->_conversationLog->get();
            }
            
            echo '<b>-- START OF LOG --</b><br />';
            
            foreach($this->_conversationLog->get() as $log){
                echo $log.'<br />';
            }
            
            echo '<b>-- END OF LOG --</b><br /><br />';
        }
        
        /**
         * Prepares the send process.
         */
        protected function _preSend(){
            $this->to = (array) $this->to;
            $this->cc = (array) $this->cc;
            $this->bcc = (array) $this->bcc;
            
            $recipients[] = $this->to;
            $recipients[] = $this->cc;
            $recipients[] = $this->bcc;
            
            // Changes two-dimensional array to a single dimension.
            $this->_recipients = call_user_func_array('array_merge', $recipients);
        }
        
        /**
         * Gets the email.
         * 
         * @param  string $recipient The recipient.
         * @return string
         */
        protected function _getEmail($recipient){
            $start = strpos($recipient, '<');
            $end = strpos($recipient, '>');
            
            if($start === false || $end === false){
                return $recipient;
            }
            
            return substr($recipient, $start + 1, $end - $start - 1);
        }
        
        /**
         * Gets the headers as a string.
         * 
         * @return string
         */
        protected function _getHeaders(){
            $_headers = [];
            
            $_headers['MIME-Version'] = self::MIME_VERSION;
            
            if(!empty($this->from)){
                $_headers['From'] = $this->from;
            }
            
            if(!empty($this->subject)){
                $_headers['Subject'] = $this->subject;
            }
            
            if(!empty($this->to)){
                $_headers['To'] = implode(self::ADDRESS_SEPARATOR, $this->to);
            }
            
            if(!empty($this->cc)){
                $_headers['Cc'] = implode(self::ADDRESS_SEPARATOR, $this->cc);
            }
            
            if(!empty($this->_contentType)){
                $_headers['Content-Type'] = $this->_contentType;
            }
            
            $_headers = array_merge($_headers, $this->_headers);
            
            $headers = '';
            
            foreach($_headers as $k => $v){
                $headers .= $k.': '.$v."\r\n";
            }
            
            return $headers."\r\n";
        }
        
        /**
         * Gets the email message.
         * 
         * @return string
         */
        protected function _getMessage(){
            $message = str_replace(["\r\n", "\r"], "\n", $this->body);
            $message = $message."\r\n".'.';
            
            return $message;
        }
        
    }
    
}
