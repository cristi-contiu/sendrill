<?php

namespace CristiContiu\SendySMTPWebhooks\Providers;

use CristiContiu\SendySMTPWebhooks\Tracker;

abstract class AbstractProvider 
{
    private $params;

    private $events;

    private $logger;

    public function __construct( $logger )
    {
        $this->setLogger($logger);
    }
 
    abstract public function authenticate();

    abstract public function readEvents();

    abstract public function processEvents( Tracker $tracker );

    abstract public function setParamsFromGlobals();


    public function getParams()
    {
        return $this->params;
    }

    public function setParams( $params )
    {
        $this->params = $params;
        return $this;
    }

    public function getEvents()
    {
        return $this->events;
    }

    public function setEvents( $events )
    {
        $this->events = $events;
        return $this;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function setLogger( $logger )
    {
        $this->logger = $logger;
        return $this;
    }

}