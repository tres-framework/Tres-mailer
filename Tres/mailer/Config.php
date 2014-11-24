<?php

namespace Tres\mailer {
    
    final class Config {
        
        /**
         * The configuration.
         * 
         * @var array
         */
        protected static $_config = [];
        
        // Prevents instantiation.
        private function __construct(){}
        private function __clone(){}
        
        /**
         * Sets the config.
         * 
         * @param array $config
         */
        public static function set(array $config){
            self::$_config = $config;
        }
        
        /**
         * Gets the config.
         * 
         * @return array
         */
        public static function get(){
            return self::$_config;
        }
        
    }
    
}
