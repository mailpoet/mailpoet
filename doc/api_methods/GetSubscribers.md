[back to list](../Readme.md)

# Get Subscribers

## `array getSubscribers(array $filter = [], int $limit = 50, int $offset = 0)`

This method returns a list of subscribers. To see the subscriber data structure, please check [getSubscriber()](GetSubscriber.md) documentation.

## Arguments

| Argument           | Type  | Default | Description                             |
| ------------------ | ----- | ------- | --------------------------------------- |
| $filter (optional) | array | empty   | Filters to retrieve subscribers         |
| $limit (optional)  | int   | 50      | The number of results that are returned |
| $offset (optional) | int   | 0       | From where to start returning data      |

### Filter

Filter argument supports following array keys.

| Key          | Type         | Description                                                                                                       |
| ------------ | ------------ | ----------------------------------------------------------------------------------------------------------------- |
| status       | string       | Specific status of subscribers. One of values: `unconfirmed`, `subscribed`, `unsubscribed`, `bounced`, `inactive` |
| listId       | int          | List id or dynamic segment id                                                                                     |
| minUpdatedAt | DateTimeInterface\|int | A `DateTime`/`DateTimeImmutable` instance, or a unix timestamp, for the minimal last-updated time of subscribers |

## Example

```php
<?php

if (class_exists(\MailPoet\API\API::class)) {
  $mailpoet_api = \MailPoet\API\API::MP('v1');

  // First page of all subscribed users in list 3
  $subscribers = $mailpoet_api->getSubscribers(
    [
      'status' => 'subscribed',
      'listId' => 3,
    ],
    100, // limit
    0    // offset
  );

  // Subscribers updated since yesterday (any status)
  $recent = $mailpoet_api->getSubscribers([
    'minUpdatedAt' => new \DateTimeImmutable('-1 day'),
  ]);
}
```
