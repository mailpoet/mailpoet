[back to list](../Readme.md)

# Untag Subscriber

## `array untagSubscriber($subscriberIdOrEmail, $tagIdOrName)`

Removes a tag from a subscriber.

The call is idempotent: if the subscriber does not have the tag, no change is made and no action is fired.
When an existing association is removed, the `mailpoet_subscriber_tag_removed` action is fired so that automations listening for the “Subscriber was untagged” trigger can run.

Unlike [Tag Subscriber](TagSubscriber.md), this method does not auto-create tags. The tag must exist.

It returns the updated subscriber. See [Get Subscriber](GetSubscriber.md) for a subscriber data structure.

## Arguments

| Argument             | Type          | Description                                                     |
| -------------------- | ------------- | --------------------------------------------------------------- |
| $subscriberIdOrEmail | int or string | An id or email of an existing subscriber.                       |
| $tagIdOrName         | int or string | An id of an existing tag, or the exact name of an existing tag. |

## Error handling

All expected errors from the API are exceptions of class `\MailPoet\API\MP\v1\APIException`.
Code of the exception is populated to distinguish between different errors.

An exception of base class `\Exception` can be thrown when something unexpected happens.

Codes description:

| Code | Description                             |
| ---- | --------------------------------------- |
| 4    | The subscriber does not exist.          |
| 25   | Missing tag name (empty string passed). |
| 29   | The tag does not exist.                 |
