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

| Code | Description                                         |
| ---- | --------------------------------------------------- |
| 3    | No lists provided                                   |
| 4    | Invalid subscriber that does not exist              |
| 5    | Invalid list that does not exist                    |
| 6    | Trying to subscribe to a WordPress Users list       |
| 7    | Trying to subscribe to a WooCommerce Customers list |
| 10   | Confirmation email failed to send                   |
