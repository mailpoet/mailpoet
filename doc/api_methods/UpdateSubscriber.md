[back to list](../Readme.md)

# Update Subscriber

## `array updateSubscriber($subscriberIdOrEmail, array $subscriber): array`

This method allows a subscriber to be updated.
The argument `$subscriber` is similar to [Add Subscriber](AddSubscriber.md) method, but the subscriber is updated instead of created.

It returns the updated subscriber. See [Get Subscriber](GetSubscriber.md) for a subscriber data structure.

If the subscriber is a WordPress user, the method does not allow updating `email`, `first_name` and `last_name`. It needs to be updated in the `wp_users` and MailPoet will synchronise the new values.

### Tracking consent

`tracking_consent` and `tracking_consent_copy` work exactly as in [Add Subscriber](AddSubscriber.md#arguments), including for a subscriber linked to a WordPress user: tracking consent is not identity data, so it still updates when `email`, `first_name` and `last_name` do not.

Two things worth knowing. An invalid `tracking_consent` value never raises an error, unlike most other fields: it is ignored, and whatever consent the subscriber already had is left untouched. And MailPoet always stamps the method as `api` and the timestamp as now, so you cannot backdate a consent record or attribute it to a different collection point.

### Tags

If the `tags` key is present in `$subscriber`, the subscriber’s tags are replaced with the provided set: any existing tags not in the new set are removed and missing ones are added. See [Add Subscriber](AddSubscriber.md#tags) for supported item formats. Omitting the `tags` key leaves existing tags untouched.

To add or remove a single tag without replacing the entire set, use [Tag Subscriber](TagSubscriber.md) or [Untag Subscriber](UntagSubscriber.md).

Adding or removing a tag fires the `mailpoet_subscriber_tag_added` or `mailpoet_subscriber_tag_removed` WordPress action, which can be used to trigger automations.

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

| Code | Description                                             |
| ---- | ------------------------------------------------------- |
| 4    | Updating a subscriber that does not exist.              |
| 13   | The subscriber couldn’t be updated in the database.     |
| 25   | A `tags` entry resolved to an empty/missing tag name.   |
| 29   | A `tags` entry referenced a tag id that does not exist. |

## Example

```php
<?php

if (class_exists(\MailPoet\API\API::class)) {
  $mailpoet_api = \MailPoet\API\API::MP('v1');

  try {
    // Update the name and replace the entire tag set with two tags.
    $subscriber = $mailpoet_api->updateSubscriber('jane.doe@example.com', [
      'first_name' => 'Janet',
      'tags' => ['VIP', 'Newsletter'],
      'cf_1' => 'San Francisco',
    ]);

    // To leave tags untouched, simply omit the 'tags' key.
    $mailpoet_api->updateSubscriber($subscriber['id'], [
      'last_name' => 'Doe-Smith',
    ]);
  } catch (\MailPoet\API\MP\v1\APIException $e) {
    error_log(sprintf('MailPoet updateSubscriber failed [%d]: %s', $e->getCode(), $e->getMessage()));
  }
}
```
