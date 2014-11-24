<?php

use Tres\mailer\Config as MailConfig;
use Tres\mailer\Connection as MailConnection;
use Tres\mailer\Mail;
use Tres\mailer\PackageInfo as MailerPackageInfo;

error_reporting(-1);
ini_set('display_errors', 1);

spl_autoload_register(function($class){
    $file = dirname(__DIR__).'/'.str_replace('\\', '/', $class.'.php');
    
    
    if(is_readable($file)){
        require_once($file);
    } else {
        if(is_file($file)){
            die($file.' is not readable.');
        } else {
            die($file.' does not exist.');
        }
    }
});

$mailerInfo = [
    'defaults' => [
        'connection' => 'server 1',
        'port'       => 25,
        'timeout'    => 300,
        'security'   => 'none'
    ],
    
    'connections' => [
    
        'server 1' => [
            'host'      => 'smtp.gmail.com',
            'port'      => 587,
            'username'  => 'example@gmail.com',
            'password'  => 'password',
            'security'  => 'TLS'
        ],
        
        'server 2' => [
            'host'      => 'smtp.live.com',
            'port'      => 587,
            'username'  => 'example@outlook.com',
            'password'  => 'password',
            'security'  => 'TLS'
        ],
        
        'server 3' => [
            'host'      => 'smtp.example.com',
            'username'  => 'email@example.com',
            'password'  => 'password'
        ],
        
        
    ]
    
];

MailConfig::set($mailerInfo);

try {
    $mainMailServer = new MailConnection('server 2');
} catch(Exception $e){
    die($e->getMessage());
}

$mail = new Mail(/*$mainMailServer*/);
$mail->isHTML();

$mail->from = 'John Doe';
$mail->to = 'to@example.com';
$mail->cc = [
    'cc1@example.com',
    'cc2@example.com'
];
$mail->bcc = 'bcc@example.com';
$mail->subject = 'Test email!';
$mail->body = '<h1>Test email</h1><b>HTML</b> is supported.';

$mail->addHeader('X-IP-Address', $_SERVER['REMOTE_ADDR']);
$mail->addHeader('X-Mailer', 'Tres Mailer/'.MailerPackageInfo::get()['version']);

if($mail->send()){
    echo 'Success!<br />';
} else {
    echo 'An error occurred.<br />';
}

$mail->displayLog();

echo '<pre>', print_r(MailerPackageInfo::get()), '</pre>';
