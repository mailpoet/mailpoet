[back to list](../Readme.md)

# Tag Subscriber

## `array tagSubscriber($subscriberIdOrEmail, $tagIdOrName)`

Adds a tag to a subscriber.

The call is idempotent: if the subscriber already has the tag, no change is made and no action is fired.
When a new tag is added, the `mailpoet_subscriber_tag_added` action is fired so that automations listening for the “Subscriber was tagged” trigger can run.

It returns the updated subscriber. See [Get Subscriber](GetSubscriber.md) for a subscriber data structure.

## Arguments

| Argument             | Type          | Description                                                                                     |
| -------------------- | ------------- | ----------------------------------------------------------------------------------------------- |
| $subscriberIdOrEmail | int or string | An id or email of an existing subscriber.                                                       |
| $tagIdOrName         | int or string | An id of an existing tag, or a tag name. A name that does not match an existing tag is created. |

A numeric string for `$tagIdOrName` is treated as an id and must match an existing tag. Any other string is treated as a tag name.

## Error handling

All expected errors from the API are exceptions of class `\MailPoet\API\MP\v1\APIException`.
Code of the exception is populated to distinguish between different errors.

An exception of base class `\Exception` can be thrown when something unexpected happens.

Codes description:

| Code | Description                             |
| ---- | --------------------------------------- |
| 4    | The subscriber does not exist.          |
| 25   | Missing tag name (empty string passed). |
| 29   | A tag referenced by id does not exist.  |
