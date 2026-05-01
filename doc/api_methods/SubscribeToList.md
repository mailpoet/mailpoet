[back to list](../Readme.md)

# Subscribe to List

## `array subscribeToList(string $subscriber_id, string $list_id [, array $options = []])`

This method allows adding an existing subscriber into a list, and handles confirmation email and admin notification email sending.

This method works exactly the same as [Subscribe to lists](SubscribeToLists.md). The only difference is the second argument which is a single list id.

## Example

```php
<?php

if (class_exists(\MailPoet\API\API::class)) {
  $mailpoet_api = \MailPoet\API\API::MP('v1');

  try {
    $mailpoet_api->subscribeToList(
      'jane.doe@example.com', // subscriber id or email
      3                       // single list id
    );
  } catch (\MailPoet\API\MP\v1\APIException $e) {
    error_log(sprintf('MailPoet subscribeToList failed [%d]: %s', $e->getCode(), $e->getMessage()));
  }
}
```
