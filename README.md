# Sendy SMTP webhooks
Allows Sendy, the popular self-hosted email marketing software, to receive and process email events from SMTP providers (like Mandrill) via authenticated webhooks.

## Installation
1. Upload the library to a folder in Sendy's root (ex: smtp-webhooks)
2. Rename `app/config.php.template` to `app/config.php` and edit the file
3. Make the required changes to Sendy's code according to your SMTP provider (see below)

## Changes to Sendy code
In order for the app to identify the subscriber's list and campaign upon receiving an event and allocate it properly, a small change must be made to Sendy's code. The required code varies between SMTP providers.

**For Mandrill**: In the files `SENDY_ROOT/scheduled.php` and `SENDY_ROOT/includes/create/send-now.php`, before `$mail->send();` add the following:

    $mail->AddCustomHeader('X-MC-Metadata: { "sendy_list_id": "' . $subscriber_list . '" }');
    $mail->AddCustomHeader('X-MC-Metadata: { "sendy_campaign_id": "' . $campaign_id . '" }');

### Supported events
- sent (message has been sent successfully)
- spam (recipient marked a message as spam)
- soft bounce (message has soft bounced)
- hard bounce (message has hard bounced)
- reject (message has been rejected by SMTP provider)

### Supported SMTP Providers
- [Mandrill](https://mandrill.com/)

Future SMTP providers can be added, by request or fork.
