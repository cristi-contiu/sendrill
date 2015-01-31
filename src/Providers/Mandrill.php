<?php

namespace CristiContiu\SendySMTPWebhooks\Providers;

use CristiContiu\SendySMTPWebhooks\Tracker;

class Mandrill extends AbstractProvider
{
    
    public function authenticate()
    {
        $params = $this->getParams();
    
        if ( $params->configWebhookKey ) {
            if ( $params->requestMethod !== 'POST' ) {
                $this->getLogger()->addDebug('Invalid request method', $params->toArray());
                return false;
            }
            if ( empty($params->requestSignature) ) {
                $this->getLogger()->addDebug('Missing Mandrill signature', $params->toArray());
                return false;
            }
            $expectedSig = $this->generateSignature($params->configWebhookKey, $params->configWebhookUrl, $params->requestBody);
            if ( $params->requestSignature !== $expectedSig ) {
                $this->getLogger()->addDebug('Wrong Mandrill webhook Key', $params->toArray());
                return false;
            }
        }

        return true;
    }

    public function readEvents()
    {
        $params = $this->getParams();
        
        if ( empty($params->requestBody) ) {
            $this->getLogger()->addDebug('Empty request body', $params->toArray());
            return false;
        }
        if ( !isset($params->requestBody['mandrill_events']) ) {
            $this->getLogger()->addDebug('Empty events array', $params->toArray());
            return false;
        }
        $events = json_decode( $params->requestBody['mandrill_events'], true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $this->getLogger()->addDebug('Invalid events JSON', $params->toArray());
            return false;
        }
        $this->setEvents($events);
        return true;
    }

    public function processEvents( Tracker $tracker )
    {
        foreach ( $this->getEvents() as $event ) {
            $name = $event['event'];
            $email = $event['msg']['email'];
            $msgID = $event['msg']['_id'];
            $listID = $event['msg']['metadata']['sendy_list_id'];
            $campaignID = $event['msg']['metadata']['sendy_campaign_id'];
            
            $this->getLogger()->addDebug("Received event '$name' for '$email'", $event);
            
            switch ( $name ) {
                case 'send': // message has been sent successfully
                    $tracker->send( $msgID, $email, $listID, $campaignID );
                    break;  
                case 'deferral': // message has been sent, but the receiving server has indicated mail is being delivered too quickly and Mandrill should slow down sending temporarily
                    $tracker->send( $msgID, $email, $listID, $campaignID );
                    break;
                case 'hard_bounce': // message has hard bounced
                    $tracker->hardBounce( $msgID, $email, $listID, $campaignID );
                    break;
                case 'soft_bounce': // message has soft bounced
                    $tracker->softBounce( $msgID, $email, $listID, $campaignID );
                    break;
                case 'spam': // recipient marked a message as spam
                    $tracker->spam( $msgID, $email, $listID, $campaignID );
                    break;
                case 'reject': // message was rejected
                    $tracker->reject( $msgID, $email, $listID, $campaignID );
                    break;
                case 'open':  // recipient opened a message; will only occur when open tracking is enabled
                case 'click': // recipient clicked a link in a message; will only occur when click tracking is enabled
                case 'unsub': // recipient unsubscribed
                default:
                    $tracker->unsupported( $name, $msgID, $email, $listID, $campaignID );
                    break;
            }
        }
    }

    public function setParamsFromGlobals() 
    {
        $params = new MandrillParams();
        $params->requestMethod = $_SERVER['REQUEST_METHOD'];
        $params->requestSignature = $_SERVER['HTTP_X_MANDRILL_SIGNATURE'];
        $params->requestBody = $_POST;
        $params->configWebhookKey = SW_MANDRILL_WEBHOOK_KEY;
        $params->configWebhookUrl = SW_MANDRILL_WEBHOOK_URL;
        
        $this->setParams($params);
    }
    
    /**
     * Generates a base64-encoded signature for a Mandrill webhook request.
     *
     * @param string $webhook_key the webhook's authentication key
     * @param string $url the webhook url
     * @param array $params the request's POST parameters
     */
    private function generateSignature($webhook_key, $url, $params) 
    {
        $signed_data = $url;
        ksort($params);
        foreach ($params as $key => $value) {
            $signed_data .= $key;
            $signed_data .= $value;
        }

        return base64_encode(hash_hmac('sha1', $signed_data, $webhook_key, true));
    }

}