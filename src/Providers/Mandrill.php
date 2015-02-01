<?php
/**
 * Mandrill SMTP provider class
 *
 * The rules of authentication and events reading are up-to-date 
 * as of February 2015 and are available in Mandrill documentation
 * at http://help.mandrill.com/forums/22050212-Webhooks-Basics
 *
 * @package    CristiContiu\SendySMTPWebhooks
 * @author     Cristi Contiu <cristi@contiu.ro>
 * @license    MIT
 * @link       https://github.com/cristi-contiu/sendy-smtp-webhooks
 */

namespace CristiContiu\SendySMTPWebhooks\Providers;

use Symfony\Component\HttpFoundation\Request;
use CristiContiu\SendySMTPWebhooks\SendyListener;

class Mandrill extends AbstractProvider
{
    /**
     * Name used to identify provider
     * @var string
     */
    protected $name = 'Mandrill';

    /**
     * Mandrill webhook key from config
     * @var string
     */
    protected $webhookKey;

    /**
     * Mandrill webhook url from config
     * @var string
     */
    protected $webhookUrl;

    /**
     * Extends common functionality of constructor
     * @param Request $request 
     * @param array $config 
     * @return null
     */
    public function __construct( Request $request, $config )
    {
        parent::__construct($request, $config);

        $this->webhookKey = $config['providers']['mandrill']['webhookKey'];
        $this->webhookUrl = $config['providers']['mandrill']['webhookUrl'];
    }

    /**
     * Authenticates the request following Mandrill instructions available at
     * http://help.mandrill.com/entries/23704122-Authenticating-webhook-requests
     *
     * @return boolean
     */
    public function authenticate()
    {
        // if no key or url provided, authentication is disabled
        if ( !$this->webhookKey || !$this->webhookUrl ) {
            $this->logger->addDebug('Authentication disabled - Mandrill webhook key and url not provided in config');
            return true;
        }

        // checking request method; POST and HEAD (for url checking) are allowed
        if ( !in_array( $this->request->getMethod(), array('POST', 'HEAD') ) ) {
            $this->logger->addDebug('Invalid request method', (array) $this->request);
            return false;
        }

        // verifying signature in custom header
        $requestSig = $this->request->headers->get('HTTP_X_MANDRILL_SIGNATURE');
        $expectedSig = $this->generateSignature($this->webhookKey, $this->webhookUrl, $this->request->request->all());
        if ( $requestSig !== $expectedSig ) {
            $this->logger->addDebug('Invalid Mandrill signature', (array) $this->request);
            return false;
        }

        return true;
    }

    /**
     * Reads and sets the events from the request
     * @return boolean
     */
    public function readEvents()
    {        
        if ( !$this->request->get('mandrill_events') ) {
            $this->logger->addDebug('Empty or missing events JSON', (array) $this->request);
            return false;
        }
        $events = json_decode( $this->request->get('mandrill_events'), true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $this->logger->addDebug('Invalid events JSON', (array) $this->request);
            return false;
        }
        $this->events = $events;
        return true;
    }

    /**
     * Processes and maps the events to sendyand updates Sendy's database
     * @param  SendyListener  $sendyListener
     * @return boolean
     */
    public function processEvents( SendyListener $sendyListener )
    {
        foreach ( $this->events as $event ) {
            $name = $event['event'];
            $email = $event['msg']['email'];
            $msgID = $event['msg']['_id'];
            $listID = $event['msg']['metadata']['sendy_list_id'];
            $campaignID = $event['msg']['metadata']['sendy_campaign_id'];
            
            $this->logger->addDebug("Received event '$name' for '$email'", $event);
            
            switch ( $name ) {
                case 'send': // message has been sent successfully
                    $sendyListener->send( $msgID, $email, $listID, $campaignID );
                    break;  
                case 'deferral': // message has been sent, but the receiving server has indicated mail is being delivered too quickly and Mandrill should slow down sending temporarily
                    $sendyListener->send( $msgID, $email, $listID, $campaignID );
                    break;
                case 'hard_bounce': // message has hard bounced
                    $sendyListener->hardBounce( $msgID, $email, $listID, $campaignID );
                    break;
                case 'soft_bounce': // message has soft bounced
                    $sendyListener->softBounce( $msgID, $email, $listID, $campaignID );
                    break;
                case 'spam': // recipient marked a message as spam
                    $sendyListener->spam( $msgID, $email, $listID, $campaignID );
                    break;
                case 'reject': // message was rejected
                    $sendyListener->reject( $msgID, $email, $listID, $campaignID );
                    break;
                case 'open':  // recipient opened a message; will only occur when open tracking is enabled
                case 'click': // recipient clicked a link in a message; will only occur when click tracking is enabled
                case 'unsub': // recipient unsubscribed
                default:
                    $sendyListener->unsupported( $name, $msgID, $email, $listID, $campaignID );
                    break;
            }
        }
    }
    
    /**
     * Generates a base64-encoded signature for a Mandrill webhook request.
     * http://help.mandrill.com/entries/23704122-Authenticating-webhook-requests
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
