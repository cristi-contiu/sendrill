<?php
/**
 * Template for provider classes with basic functionality
 *
 * @package    CristiContiu\SendySMTPWebhooks
 * @author     Cristi Contiu <cristi@contiu.ro>
 * @license    MIT
 * @link       https://github.com/cristi-contiu/sendy-smtp-webhooks
 */

namespace CristiContiu\SendySMTPWebhooks\Providers;

use Symfony\Component\HttpFoundation\Request;
use Monolog\Logger;
use CristiContiu\SendySMTPWebhooks\SendyListener;

abstract class AbstractProvider 
{
    /**
     * Name used to identify provider
     * @var string
     */
    protected $name;

    /**
     * @var Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var Monolog\Logger
     */
    protected $logger;

    /**
     * Events received from SMTP provider via webhook
     * @var array
     */
    protected $events;

    /**
     * Sets up common properties
     * @param Request $request 
     * @param array   $config 
     * @return null
     */
    public function __construct( Request $request, $config )
    {
        $this->request = $request;

        $this->logger = new Logger( $this->name );
        $this->logger->pushHandler( $config['logHandler'] );
    }

    /**
     * Authenticates the request following provider's instructions
     * @return boolean
     */
    abstract public function authenticate();

    /**
     * Reads and sets the events from the request
     * @return boolean
     */
    abstract public function readEvents();

    /**
     * Processes and maps the events to sendyand updates Sendy's database
     * @param  SendyListener  $sendyListener
     * @return boolean
     */
    abstract public function processEvents( SendyListener $sendyListener );

}
