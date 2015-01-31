<?php

namespace CristiContiu\SendySMTPWebhooks\Providers;

class MandrillParams 
{

    public $requestMethod;

    public $requestSignature;

    public $requestBody;

    public $configWebhookKey;

    public $configWebhookUrl;
    
    public function toArray()
    {
        return get_object_vars($this);
    }

}