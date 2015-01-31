<?php

// Add to sendy/scheduled.php:532, sendy/includes/create/send-now.php:508
// $mail->AddCustomHeader('X-MC-Metadata: { "sendy_list_id": "' . $subscriber_list . '" }');
// $mail->AddCustomHeader('X-MC-Metadata: { "sendy_campaign_id": "' . $campaign_id . '" }');

/**
 *  Sendy-Webhook settings
 */
define('SW_SENDY_CONFIG_FILE',     '/../includes/config.php');
define('SW_MANDRILL_WEBHOOK_URL',  'http://email.bixel.ro/smtp-webhooks/mandrill');   // the webhook URL EXACTLY as entered in Mandrill (including query parameters)
define('SW_MANDRILL_WEBHOOK_KEY',  'FSmbtWP6xKk4o1FsBL1gPg');                    // the webhook key given by Mandrill
define('SW_ALLOWED_SOFT_BOUNCES',  3);                                           // number of allowed soft bounces before converting to hard bounce
define('SW_DEBUG',                 true);
define('SW_LOG_FILE',              'app/app.log');