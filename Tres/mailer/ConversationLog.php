<?php

namespace Tres\mailer {
    
    class ConversationLog {
        
        /**
         * The SMTP conversation log.
         * 
         * @var array
         */
        protected $_conversation = [];
        
        /**
         * Adds to conversation log.
         * 
         * @param string $log The log.
         */
        public function add($name, $log){
            $this->_conversation[] = "[{$name}]\t ".$log;
        }
        
        /**
         * Gets the conversation log.
         * 
         * @return array
         */
        public function get(){
            return $this->_conversation;
        }
        
    }
    
}
