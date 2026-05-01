[back to list](../Readme.md)

# Unsubscribe from all lists and change subscriber status

## `array unsubscribe(string $subscriber_id)`

This method removes a subscriber from all lists and updates its status to 'unsubscribed'.

It returns a subscriber. See [Get Subscriber](GetSubscriber.md) for returned data structure.

## Arguments

### string `$subscriber_id` (required)

An id or email of an existing subscriber. An `\Exception` is thrown when an id or email doesn't match any subscriber.

## Error handling

All expected errors from the API are exceptions of class `\MailPoet\API\MP\v1\APIException`.
Code of the exception is populated to distinguish between different errors.

An exception of base class `\Exception` can be thrown when something unexpected happens.

Codes description:

| Code | Description                                  |
| ---- | -------------------------------------------- |
| 4    | Invalid subscriber that does not exist       |
| 24   | Subscriber already has 'unsubscribed' status |

## Example

```php
<?php

if (class_exists(\MailPoet\API\API::class)) {
  $mailpoet_api = \MailPoet\API\API::MP('v1');

  try {
    $mailpoet_api->unsubscribe('jane.doe@example.com');
  } catch (\MailPoet\API\MP\v1\APIException $e) {
    if ($e->getCode() === 24 /* SUBSCRIBER_ALREADY_UNSUBSCRIBED */) {
      // Already unsubscribed - safe to ignore depending on the use case.
      return;
    }
    error_log(sprintf('MailPoet unsubscribe failed [%d]: %s', $e->getCode(), $e->getMessage()));
  }
}
```
