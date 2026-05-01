[back to list](../Readme.md)

# Unsubscribe from List

## `array unsubscribeFromList(string $subscriber_id, string $list_id)`

This method removes a subscriber from given list.

This method works exactly the same as [Unsubscribe from Lists](UnsubscribeFromLists.md). The only difference is the second argument which is a single list id.

## Example

```php
<?php

if (class_exists(\MailPoet\API\API::class)) {
  $mailpoet_api = \MailPoet\API\API::MP('v1');

  try {
    $mailpoet_api->unsubscribeFromList(
      'jane.doe@example.com', // subscriber id or email
      3                       // single list id
    );
  } catch (\MailPoet\API\MP\v1\APIException $e) {
    error_log(sprintf('MailPoet unsubscribeFromList failed [%d]: %s', $e->getCode(), $e->getMessage()));
  }
}
```
