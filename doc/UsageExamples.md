[back to readme](Readme.md)

# Usage Examples

Common usage is a rendering of a subscription form and processing it.

## Fetching data for a subscription form

```php
<?php

if (class_exists(\MailPoet\API\API::class)) {
  // Get MailPoet API instance
  $mailpoet_api = \MailPoet\API\API::MP('v1');
  // Get available list so that a subscriber can choose in which to subscribe
  $lists = $mailpoet_api->getLists();
  // Get subscriber fields to know what fields can be rendered within a form
  $subscriber_form_fields = $mailpoet_api->getSubscriberFields();
}
```

## Processing a subscription form

```php
<?php

if (class_exists(\MailPoet\API\API::class)) {
  // Get MailPoet API instance
  $mailpoet_api = \MailPoet\API\API::MP('v1');

  // Fill subscribed data from $_POST (for simplicity it expects that subscriber field ids are used as input names)
  $subscriber = [];
  $subscriber_form_fields = $mailpoet_api->getSubscriberFields();
  foreach ($subscriber_form_fields as $field) {
    if (!isset($_POST[$field['id']])) {
      continue;
    }
    $subscriber[$field['id']] = $_POST[$field['id']];
  }
  $list_ids = $_POST['list_ids'];

  // Check if subscriber exists. If subscriber doesn't exist an exception is thrown
  try {
    $get_subscriber = $mailpoet_api->getSubscriber($subscriber['email']);
  } catch (\Exception $e) {}

  try {
    if (!$get_subscriber) {
      // Subscriber doesn't exist let's create one
      $mailpoet_api->addSubscriber($subscriber, $list_ids);
    } else {
      // In case subscriber exists just add him to new lists
      $mailpoet_api->subscribeToLists($subscriber['email'], $list_ids);
    }
  } catch (\Exception $e) {
    $error_message = $e->getMessage();
  }
}
```
