[back to list](../Readme.md)

# Unsubscribe from Lists

## `array unsubscribeFromLists(string $subscriber_id, array $list_ids)`

This method removes a subscriber from given lists.

It returns a subscriber. See [Get Subscriber](GetSubscriber.md) for a subscriber data structure.

## Arguments
### string `$subscriber_id` (required)
An id or email of an existing subscriber. An `\Exception` is thrown when an id or email doesn't match any subscriber.

### array `$list_ids` (required)
An array of list ids. An `\Exception` is thrown if any of list ids is invalid. In such a case the subscriber remains subscribed to all lists.
