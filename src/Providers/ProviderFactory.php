<?php

namespace CristiContiu\SendySMTPWebhooks\Providers;

use Monolog\Logger;

class ProviderFactory 
{
    static public function createByName( $name, $globalLogFile ) 
    {
        switch ( $name ) {
            case 'mandrill' :
                $logger = new Logger('Mandrill');
                $logger->pushHandler( $globalLogFile );
                $provider = new Mandrill( $logger );
                break;
            default :
                $provider = false;
                break;
        }
        return $provider;
    }
}