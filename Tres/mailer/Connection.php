<?php

namespace Tres\mailer {
    
    use Exception;
    
    class MailConnectionException extends Exception implements ExceptionInterface {}
    
    class Connection {
        
        /**
         * The name of the connection.
         * 
         * @var string
         */
        protected $_connectionName = '';
        
        /**
         * The connection config.
         * 
         * @var array
         */
        protected $_config = [];
        
        /**
         * The SMTP socket connection.
         * 
         * @var resource
         */
        protected $_connection = null;
        
        /**
         * Holds the ConversationLog object.
         * 
         * @var \packages\Tres\mailer\ConversationLog
         */
        protected $_conversationLog = [];
        
        /**
         * Sets the mail connection.
         * 
         * @param string $connName Connection name; to load from the config.
         */
        public function __construct($connName = null){
            $default = Config::get()['defaults'];
            
            if(!isset($connName)){
                $connName = $default['connection'];
            }
            
            $this->_connectionName = $connName;
            
            if(in_array($this->_connectionName, Config::get()['connections'])){
                throw new MailConnectionException('Mail connection not found!');
            } else {
                $this->_config = Config::get()['connections'][$this->_connectionName];
            }
            
            if(!isset($this->_config['port'])){
                $this->_config['port'] = $default['port'];
            }
            
            if(!isset($this->_config['timeout'])){
                $this->_config['timeout'] = $default['timeout'];
            }
            
            if(!isset($this->_config['security'])){
                $this->_config['security'] = $default['security'];
            }
            
            $this->_conversationLog = new ConversationLog();
            $this->_startConversation();
        }
        
        /**
         * Returns the ConversationLog object.
         * 
         * @return \packages\Tres\mailer\ConversationLog
         */
        public function getLog(){
            return $this->_conversationLog;
        }
        
        /**
         * Sends a command to the SMTP server.
         * 
         * @return string The server response.
         */
        public function sendCommand($command){
            fputs($this->_connection, $command."\r\n");
            
            return $this->_getResponse();
        }
        
        /**
         * Disconnects connection.
         */
        public function close(){
            fclose($this->_connection);
        }
        
        /**
         * Starts to communicate with the SMTP server.
         */
        protected function _startConversation(){
            if(!$this->_connect()){
                throw new MailConnectionException('Could not connect to SMTP server.');
            }
            
            $this->_conversationLog->add('EHLO', $this->sendCommand('EHLO')); // FQDN?
            
            if(!$this->_auth()){
                throw new MailConnectionException('Could not authenticate with SMTP server.');
            }
        }
        
        /**
         * Starts the SMTP connection.
         * 
         * @return bool
         */
        protected function _connect(){
            $host = $this->_config['host'];
            
            switch(strtolower($this->_config['security'])){
                case 'ssl':
                    throw new MailConnectionException('SSL is not supported because of the POODLE vulnerability.');
                break;
                
                case 'tls':
                default:
                    $host = 'tcp://'.$host;
                break;
            }
            
            if(!$this->_connection = fsockopen($host,
                                               $this->_config['port'],
                                               $errno,
                                               $errstr,
                                               $this->_config['timeout']
            )){
                return false;
            }
            
            $this->_conversationLog->add('CONNECTION', $this->_getResponse());
            
            return true;
        }
        
        /**
         * Authenticates through SMTP.
         * 
         * @return bool
         */
        protected function _auth(){
            if(strtolower($this->_config['security']) == 'tls'){
                $this->_conversationLog->add('STARTTLS', $this->sendCommand('STARTTLS'));
                
                stream_socket_enable_crypto($this->_connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                
                $this->_conversationLog->add('EHLO', $this->sendCommand('EHLO')); // FQDN?
            }
            
            $this->_conversationLog->add('AUTH LOGIN', $this->sendCommand('AUTH LOGIN'));
            $this->_conversationLog->add('USERNAME', $this->sendCommand($this->_encode($this->_config['username'])));
            $this->_conversationLog->add('PASSWORD', $this->sendCommand($this->_encode($this->_config['password'])));
            
            return true;
        }
        
        /**
         * Gets the server response.
         * 
         * @return string
         */
        protected function _getResponse(){
            stream_set_timeout($this->_connection, $this->_config['timeout']);
            
            $response = '';
            
            while(($line = fgets($this->_connection, 515)) != false) {
                $response .= trim($line).'<br />';
                
                if(substr($line, 3, 1) == ' '){
                    break;
                }
            }
            
            return trim($response);
        }
        
        protected function _encode($str){
            return base64_encode($str);
        }
        
    }
    
}
