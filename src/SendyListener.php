<?php
/**
 * Mail events listener that updates subscriber status in Sendy's database
 *
 * @package    CristiContiu\SendySMTPWebhooks
 * @author     Cristi Contiu <cristi@contiu.ro>
 * @license    MIT
 * @link       https://github.com/cristi-contiu/sendy-smtp-webhooks
 */

namespace CristiContiu\SendySMTPWebhooks;

use Monolog\Logger;

class SendyListener
{
    /**
     * @var mysqli
     */
    private $mysqli;

    /**
     * @var Monolog\Logger
     */
    private $logger;

    /**
     * Sets up logger and database connection
     * @param array $config 
     */
    public function __construct( $config )
    {
        $this->logger = new Logger('SendyListener');
        $this->logger->pushHandler($config['logHandler']);

        extract($config['sendy']);

        $this->mysqli = new \mysqli( $dbHost, $dbUser, $dbPass, $dbName, $dbPort );
        if ( $this->mysqli->connect_errno ) {
            $this->logger->addError( "Failed to connect to MySQL: (" . $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error );
            throw new \Exception( "Database error", 500 );
        }

        if ( !$this->mysqli->set_charset($charset) ) {
            $this->logger->addError( "Failed to set MySQL character set '$charset': (" . $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error );
            throw new \Exception( "Database error", 500 );
        }
    }

    public function send( $msgID, $email, $listID, $campaignID )
    {
        $sqls = "UPDATE subscribers SET messageID = '{msgID}', last_campaign = '{campaignID}' "
              . "WHERE email = '{email}' AND list = '{listID}' LIMIT 1;";
        $this->execute('send', $sqls, $msgID, $email, $listID, $campaignID);
    }
    
    public function reject( $msgID, $email, $listID, $campaignID )
    {
        $sqls = "UPDATE subscribers SET bounced = 1, messageID = '{msgID}', last_campaign = '{campaignID}' "
              . "WHERE email = '{email}' AND list = '{listID}' LIMIT 1;";
        $this->execute('reject', $sqls, $msgID, $email, $listID, $campaignID);
    }

    public function hardBounce( $msgID, $email, $listID, $campaignID )
    {
        $sqls = "UPDATE subscribers SET bounced = 1 "
              . "WHERE email = '{email}' AND messageID = '{msgID}' LIMIT 1;";
        $this->execute('hardBounce', $sqls, $msgID, $email, $listID, $campaignID);
    }

    public function softBounce( $msgID, $email, $listID, $campaignID )
    {
    	$asb = $this->mysqli->real_escape_string( intval(SW_ALLOWED_SOFT_BOUNCES) );
        $sqls = "UPDATE subscribers SET bounce_soft = bounce_soft + 1, bounced = IF(bounce_soft > '$asb', 1, bounced) "
              . "WHERE email = '{email}' AND messageID = '{msgID}' LIMIT 1;";
        $this->execute('softBounce', $sqls, $msgID, $email, $listID, $campaignID);
    }

    public function spam( $msgID, $email, $listID, $campaignID )
    {
        $sqls = "UPDATE subscribers SET complaint = 1 "
              . "WHERE email = '{email}' AND messageID = '{msgID}' LIMIT 1; ";
        $this->execute('spam', $sqls, $msgID, $email, $listID, $campaignID);
    }
    
    public function unsupported( $action, $msgID, $email, $listID, $campaignID )
    {
        $this->logger->addDebug("Tracker action '$action' is not supported - sent for '$email', msgID '$msgID'");
    }

    private function execute( $action, $sqls, $msgID, $email, $listID, $campaignID ) 
    {   
        $sqls = (array) $sqls;
        foreach ( $sqls as $sql ) {
            $sql = str_replace("{msgID}", $this->mysqli->real_escape_string($msgID), $sql);
            $sql = str_replace("{email}", $this->mysqli->real_escape_string($email), $sql);
            $sql = str_replace("{listID}", $this->mysqli->real_escape_string($listID), $sql);
            $sql = str_replace("{campaignID}", $this->mysqli->real_escape_string($campaignID), $sql);
            $this->logger->addDebug("Tracker->$action executes query: '$sql'");
            if ( $this->mysqli->query($sql) == false ) {
                $this->logger->addError("MySQL error in Tracker->$action : " . $this->mysqli->error);
            }
        }
    }
}