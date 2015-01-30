# Sendy SMTP webhooks
Allows Sendy, the popular self-hosted email marketing software, to receive authenticated messages from SMTP providers, like Mandrill, via webhooks.

## Installation
1. Upload the library to a folder in Sendy's root (ex: smtp-webhooks)
2. Edit the *config.php* file
3. Make the required changes to Sendy's code (see below)

## Changes to Sendy code
The changes to Sendy's code are required to identify the subscriber's list and campaign and attribute the event properly. These vary between SMTP providers.

**For Mandrill**: In the files *sendy_root*/scheduled.php and *sendy_root*/includes/create/send-now.php, before `$mail->send();` add the following:

    $mail->AddCustomHeader('X-MC-Metadata: { "sendy_list_id": "' . $subscriber_list . '" }');
    $mail->AddCustomHeader('X-MC-Metadata: { "sendy_campaign_id": "' . $campaign_id . '" }');


## Development
Future SMTP providers can be added, by request or fork.
