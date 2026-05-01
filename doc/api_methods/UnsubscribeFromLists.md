[back to list](../Readme.md)

# Unsubscribe from Lists

## `array unsubscribeFromLists(string $subscriber_id, array $list_ids)`

This method removes a subscriber from given lists.

It returns a subscriber. See [Get Subscriber](GetSubscriber.md) for a subscriber data structure.

## Arguments

### string `$subscriber_id` (required)

An id or email of an existing subscriber. An `\Exception` is thrown when an id or email doesn't match any subscriber.

### array `$list_ids` (required)

An array of list ids. An `\Exception` is thrown if any of list ids are invalid. In such a case the subscriber remains subscribed to all lists.

## Error handling

All expected errors from the API are exceptions of class `\MailPoet\API\MP\v1\APIException`.
Code of the exception is populated to distinguish between different errors.

An exception of base class `\Exception` can be thrown when something unexpected happens.

Codes description:

| Code | Description                                                 |
| ---- | ----------------------------------------------------------- |
| 3    | No lists provided                                           |
| 4    | Invalid subscriber that does not exist                      |
| 5    | Invalid list that does not exist                            |
| 6    | Trying to unsubscribe from a WordPress Users list           |
| 7    | Trying to unsubscribe from a WooCommerce Customers list     |
| 8    | Trying to unsubscribe from a list that doesn’t support that |

## Example

```php
<?php

if (class_exists(\MailPoet\API\API::class)) {
  $mailpoet_api = \MailPoet\API\API::MP('v1');

  try {
    $mailpoet_api->unsubscribeFromLists(
      'jane.doe@example.com',
      [3, 7] // list ids
    );
  } catch (\MailPoet\API\MP\v1\APIException $e) {
    // Note: this only removes the per-list subscription. To unsubscribe
    // the subscriber from everything and flip their global status, use
    // unsubscribe() (see UnsubscribeGlobally.md).
    error_log(sprintf('MailPoet unsubscribeFromLists failed [%d]: %s', $e->getCode(), $e->getMessage()));
  }
}
```
