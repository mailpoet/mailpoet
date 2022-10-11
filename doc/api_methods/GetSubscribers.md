[back to list](../Readme.md)

# Get Subscribers

## `array getSubscribers(array $filter = [], int $limit = 50, int $offset = 0)`

This method returns a list of subscribers.

## Arguments

| Argument           | Type  | Default | Description                             |
| ------------------ | ----- | ------- | --------------------------------------- |
| $filter (optional) | array | empty   | Filters to retrieve subscribers         |
| $limit (optional)  | int   | 50      | The number of results that are returned |
| $offset (optional) | int   | 0       | From where to start returning data      |

### Filter

Filter argument supports following array keys.

| Key            | Type         | Description                                                                                                       |
| -------------- | ------------ | ----------------------------------------------------------------------------------------------------------------- |
| status         | string       | Specific status of subscribers. One of values: `unconfirmed`, `subscribed`, `unsubscribed`, `bounced`, `inactive` |
| list_id        | int          | List id or dynamic segment id                                                                                     |
| min_updated_at | DateTime\int | DateTime object or timestamp of the minimal last update of subscribers                                            |

## A subscriber data structure

### Subscriber

| Property                 | Type         | Limits    | Description                                                                                                                    |
| ------------------------ | ------------ | --------- | ------------------------------------------------------------------------------------------------------------------------------ |
| id                       | string       | 11 chars  | Id of the subscriber                                                                                                           |
| wp_user_id               | string\|null | 20 chars  | Id of a WordPress user associated with the subscriber                                                                          |
| is_woocommerce_user      | string       | -         | A flag telling whether the user is also a WooCommerce customer. Possible values are: `1`, `0`                                  |
| first_name               | string       | 255 chars | Fist name of the subscriber.                                                                                                   |
| last_name                | string       | 255 chars | Last name of the subscriber.                                                                                                   |
| email                    | string       | 150 chars | Email address of the subscriber.                                                                                               |
| status                   | string       | -         | Status of the subscriber. Possible values are: `unconfirmed`, `subscribed`, `unsubscribed`, `bounced`, `inactive`              |
| subscribed_ip            | string\|null | 45 chars  | An IP address used for subscription.                                                                                           |
| confirmed_ip             | string\|null | 45 chars  | An IP address used for confirmation.                                                                                           |
| confirmed_at             | string\|null | -         | UTC time of subscription confirmation in 'Y-m-d H:i:s' format                                                                  |
| created_at               | string\|null | -         | UTC time of creation in 'Y-m-d H:i:s' format                                                                                   |
| updated_at               | string       | -         | UTC time of last update in 'Y-m-d H:i:s' format                                                                                |
| deleted_at               | string\|null | -         | This property in not null in case that list is in trash and contains UTC time in 'Y-m-d H:i:s' format.                         |
| unconfirmed_data         | string\|null | 65K chars | May contain serialized subscriber data in case when there are pending changes waiting for a confirmation from a subscriber     |
| source                   | string\|null | -         | Possible values: `form`,`imported`,`administrator`,`api`,`wordpress_user`,`woocommerce_user`,`woocommerce_checkout`,`unknown`) |
| count_confirmations      | string       | 11 chars  | Counter for confirmation emails                                                                                                |
| subscriptions            | array        | -         | List of subcriber subscriptions                                                                                                |
| tags                     | array        | -         | List of subcriber tags                                                                                                         |
| cf\_{custom_field['id']} | string       | 65K chars | A custom subscriber field value (see [Get Subscriber Fields](GetSubscriberFields.md)                                           |

### Subscriber's subscription

| Property      | Type   | Limits   | Description                                                                          |
| ------------- | ------ | -------- | ------------------------------------------------------------------------------------ |
| id            | string | 11 chars | Id of relation                                                                       |
| subscriber_id | string | 11 chars | Id of subscriber                                                                     |
| segment_id    | string | 11 chars | Id of a list                                                                         |
| status        | string | -        | Status of a subscription for the list. Possible values: `subscribed`, `unsubscribed` |
| created_at    | string | -        | UTC time of creation in 'Y-m-d H:i:s' format                                         |
| updated_at    | string | -        | UTC time of last update in 'Y-m-d H:i:s' format                                      |

### Subscriber's tag

| Property      | Type   | Limits   | Description                                     |
| ------------- | ------ | -------- | ----------------------------------------------- |
| id            | string | 11 chars | Id of relation                                  |
| subscriber_id | string | 11 chars | Id of subscriber                                |
| tag_id        | string | 11 chars | Id of a list                                    |
| name          | string | -        | Name of a tag                                   |
| created_at    | string | -        | UTC time of creation in 'Y-m-d H:i:s' format    |
| updated_at    | string | -        | UTC time of last update in 'Y-m-d H:i:s' format |

### Response Example

```php
<?php
[
  'id' => '10',
  'wp_user_id' => '72',
  'is_woocommerce_user' => '1',
  'first_name' => 'John',
  'last_name' => 'Doe',
  'email' => 'email@example.com',
  'status' => 'subscribed',
  'subscribed_ip' => '127.0.0.1',
  'confirmed_ip' => NULL,
  'confirmed_at' => NULL,
  'created_at' => '2019-05-07 07:24:37',
  'updated_at' => '2019-05-14 08:43:08',
  'deleted_at' => NULL,
  'unconfirmed_data' => NULL,
  'source' => 'woocommerce_user',
  'count_confirmations' => '0',
  'subscriptions' => [
    0 => [
      'id' => '3',
      'subscriber_id' => '10',
      'segment_id' => '1',
      'status' => 'subscribed',
      'created_at' => '2019-05-07 07:24:37',
      'updated_at' => '2019-05-07 07:24:37',
    ],
    1 => [
      'id' => '13',
      'subscriber_id' => '10',
      'segment_id' => '2',
      'status' => 'unsubscribed',
      'created_at' => '2019-05-14 08:43:08',
      'updated_at' => '2019-05-14 08:43:08',
    ],
  ],
  'tags' => [
    0 => [
      'id' => '2',
      'subscriber_id' => '10',
      'tag_id' => '1',
      'name' => 'Alpha',
      'created_at' => '2019-05-17 05:24:37',
      'updated_at' => '2019-05-17 05:24:37',
    ],
    1 => [
      'id' => '4',
      'subscriber_id' => '10',
      'tag_id' => '5',
      'name' => 'Beta',
      'created_at' => '2020-03-07 15:21:37',
      'updated_at' => '2020-03-07 15:21:37',
    ],
  ],
  'cf_1' => 'US',
  'cf_2' => 'New York',
];
```

## Error handling

All expected errors from the API are exceptions of class `\MailPoet\API\MP\v1\APIException`.
Code of the exception is populated to distinguish between different errors.

An exception of base class `\Exception` can be thrown when something unexpected happens.

Codes description:

| Code | Description                                  |
| ---- | -------------------------------------------- |
| 4    | Asking for a subscriber that does not exist. |
