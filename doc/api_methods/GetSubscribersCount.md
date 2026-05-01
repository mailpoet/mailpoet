[back to list](../Readme.md)

# Get Subscribers Count

## `int getSubscribersCount(array $filter = [])`

This method returns the count of subscribers by a filter.

## Arguments

| Argument           | Type  | Default | Description                                  |
| ------------------ | ----- | ------- | -------------------------------------------- |
| $filter (optional) | array | empty   | Filters to retrieve the count of subscribers |

### Filter

To see supported filters, please check [getSubscribers()](GetSubscribers.md) documentation.

## Example

```php
<?php

if (class_exists(\MailPoet\API\API::class)) {
  $mailpoet_api = \MailPoet\API\API::MP('v1');

  // Total subscribed users (no filter)
  $total = $mailpoet_api->getSubscribersCount(['status' => 'subscribed']);

  // Subscribed users in a specific list, useful for paginating getSubscribers()
  $count_in_list = $mailpoet_api->getSubscribersCount([
    'status' => 'subscribed',
    'listId' => 3,
  ]);
}
```
