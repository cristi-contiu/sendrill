<?php
/**
 * Front controller for Sendy SMTP Webhooks
 *
 * @package    CristiContiu\SendySMTPWebhooks
 * @author     Cristi Contiu <cristi@contiu.ro>
 * @license    MIT
 * @link       https://github.com/cristi-contiu/sendy-smtp-webhooks
 */

require_once(__DIR__ . '/vendor/autoload.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use CristiContiu\SendySMTPWebhooks\App;

$request = Request::createFromGlobals();
$config = App::getConfigFromFiles();
$response = App::handle($request, $config);
$response->send();
