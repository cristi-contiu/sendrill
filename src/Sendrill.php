<?php
/**
 * Sendrill application class
 *
 * @package    CristiContiu\Sendrill
 * @author     Cristi Contiu <cristi@contiu.ro>
 * @license    MIT
 * @link       https://github.com/cristi-contiu/sendrill
 */

namespace CristiContiu\Sendrill;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use CristiContiu\Sendrill\Providers;
use CristiContiu\Sendrill\SendyListener;

class Sendrill
{
    /**
     * Handles the HTTP Request and returns a response
     *
     * @param  Request   $request 
     * @param  array     $config 
     * @return Response 
     */
    static public function handle( Request $request, $config )
    {
        $appLog = new Logger('App');
        $appLog->pushHandler($config['logHandler']);

        // checking and creating requested povider
        $getProvider = $request->query->get('provider');
        if ( isset($config['providers'][$getProvider]) ) {
            $provider = new $config['providers'][$getProvider]['class']($request, $config);
        } else {
            $appLog->addError("Unsupported provider", array($getProvider));
            return new Response('Unsupported provider', Response::HTTP_NOT_IMPLEMENTED);
        }

        if ( $provider->authenticate() == false ) {
            $appLog->addError('Unauthorized request', array($request->server->get('REMOTE_ADDR')));
            return new Response('Unauthorized request', Response::HTTP_UNAUTHORIZED);
        }

        if ( $provider->readEvents() == false ) {
            $appLog->addError('Invalid input');
            return new Response('Invalid input', Response::HTTP_BAD_REQUEST);
        }

        try {
            $sendyListener = new SendyListener($config);
        } catch (\Exception $exc) {
            return new Response($exc->getMessage(), $exc->getCode());
        }

        $provider->processEvents( $sendyListener );
        return new Response('OK', Response::HTTP_OK);
    }

    /**
     * Reads default and Sendy configs and sets app-wide logger handler
     *
     * @return array 
     */
    static public function getConfigFromFiles()
    {
        $config = (array) require __DIR__ . '/../app/fallback_config.php';
        // check and include local config file
        if ( file_exists(__DIR__ . '/../app/config.php') ) {
            $localConfig = (array) include __DIR__ . '/../app/config.php';
            $config = array_replace_recursive($config, $localConfig);
        }
        $config['sendy'] = self::getSendyConfig($config['sendyConfigFile']);
        $config['logHandler'] = new StreamHandler(__DIR__ . '/../' . $config['logFile'], $config['debug'] ? Logger::DEBUG : Logger::INFO);
        return $config;
    }

    /**
     * Returns variables defined in Sendy config as array
     *
     * @param  string $sendyConfigFile 
     * @return array 
     */
    static public function getSendyConfig( $sendyConfigFile )
    {
        require __DIR__ . '/../' . $sendyConfigFile; 
        unset($sendyConfigFile);
        return get_defined_vars();
    }
}
