<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use CristiContiu\SendySMTPWebhooks\Tracker;
use CristiContiu\SendySMTPWebhooks\Providers\ProviderFactory;

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/app/config.php');
require_once(__DIR__ . SW_SENDY_CONFIG_FILE);

$globalLogHandler = new StreamHandler(__DIR__ . '/' . SW_LOG_FILE, SW_DEBUG ? Logger::DEBUG : Logger::INFO);
$logger = new Logger('App');
$logger->pushHandler($globalLogHandler);

try {
    $requestedProvider = rtrim($_GET['provider'], "/");
    $provider = ProviderFactory::createByName( $requestedProvider , $globalLogHandler );
    if ( $provider == false ) {
        $logger->addError('Unsupported provider', $_GET);
        throw new Exception('Unsupported provider', 500);
    }
    
    $provider->setParamsFromGlobals();
    
    if ( $provider->authenticate() == false ) {
        $logger->addError('Unauthorized', $provider->getParams()->toArray());
        throw new Exception('Unauthorized', 401);
    }

    if ( $provider->readEvents() == false ) {
        $logger->addError('Invalid input', $provider->getParams()->toArray());
        throw new Exception('Invalid input', 400);
    }

    $tracker = new Tracker( $globalLogHandler, $dbHost, $dbUser, $dbPass, $dbName, $dbPort, $charset );
    $provider->processEvents( $tracker );
} catch (Exception $exc) {
    if ( !headers_sent() ) {
        header( $_SERVER['SERVER_PROTOCOL'] . ' ' . $exc->getCode() . ' ' . $exc->getMessage() );
    }
    die( $exc->getCode() . ' ' . $exc->getMessage() );
}