<?php
/**
 * Front controller for Sendrill
 *
 * @package    CristiContiu\Sendrill
 * @author     Cristi Contiu <cristi@contiu.ro>
 * @license    MIT
 * @link       https://github.com/cristi-contiu/sendrill
 */

require_once(__DIR__ . '/vendor/autoload.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use CristiContiu\Sendrill\Sendrill;

$request = Request::createFromGlobals();
$config = Sendrill::getConfigFromFiles();
$response = Sendrill::handle($request, $config);
$response->send();
