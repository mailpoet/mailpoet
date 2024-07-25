[back to list](../Readme.md)

# Update Subscriber

## `array updateSubscriber($subscriberIdOrEmail, array $subscriber): array`

This method allows a subscriber to be updated.
The argument `$subscriber` is similar to [Add Subscriber](AddSubscriber.md) method, but the subscriber is updated instead of created.

It returns the updated subscriber. See [Get Subscriber](GetSubscriber.md) for a subscriber data structure.

If the subscriber is a WordPress user, the method does not allow updating `email`, `first_name` and `last_name`. It needs to be updated in the `wp_users` and MailPoet will synchronise the new values.

## Arguments

| Argument             | Type          | Description                               |
| -------------------- | ------------- | ----------------------------------------- |
| $subscriberIdOrEmail | string or int | An id or email of an existing subscriber. |
| $subscriber          | array         | Subscriber data that will be updated      |

## Error handling

All expected errors from the API are exceptions of class `\MailPoet\API\MP\v1\APIException`.
Code of the exception is populated to distinguish between different errors.

An exception of base class `\Exception` can be thrown when something unexpected happens.

Codes description:

| Code | Description                                        |
| ---- | -------------------------------------------------- |
| 4    | Updating a subscriber that does not exist.         |
| 13   | The subscriber couldn’t be updated in the database |
