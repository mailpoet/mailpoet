[back to list](../Readme.md)

# Add Subscriber

## `array addSubscriber(array $subscriber [, array $list_ids = [], array $options = []])`

This method allows a subscriber to be created, adds them into lists, and handles confirmation email and admin notification email sending and welcome email scheduling.

If sign-up confirmation (double opt-in) is enabled in the MailPoet settings a subscriber is created with status `unconfirmed` otherwise the status is set to `subscribed`.

- _A confirmation email_ is an email which is sent to a subscriber so that they can confirm his subscription. It is sent only if sign-up confirmation is enabled in the MailPoet settings.
- _A welcome email_ is an automatic email which is sent to a new subscriber. This email is scheduled only if sign-up confirmation is disabled and a welcome email is configured for some of given lists. In case of required sign-up confirmation, it is scheduled later after a subscriber confirms the subscription.
- _An admin notification email_ is sent to the site admin to inform them about a new subscription. It is sent only if the notification feature is enabled in the MailPoet setting.

All these emails can be disabled using `$options`.

A subscriber can be added only once. This method throws an `\Exception` in case of adding an existing subscriber.
This method also throws an `\Exception` in case that some of `$list_ids` is invalid. In such a case a subscriber is created, but is not subscribed to any list.
There might be other `\Exceptions` because of some invalid input data such a invalid email address etc.

It returns a new subscriber. See [Get Subscriber](GetSubscriber.md) for a subscriber data structure.

## Arguments

### `$subscriber` (required)

An associative array containing subscriber data which contains default properties (email, first_name, last_name) and custom subscriber fields which were defined in MailPoet.
It has to contain an email and all required custom fields. To get defined custom fields see [Get Subscriber Fields](GetSubscriberFields.md)

| Property                   | Type                | Limits    | Description                                                                                                                                                                   |
| -------------------------- | ------------------- | --------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| email (required)           | string              | 150 chars | a valid email address                                                                                                                                                         |
| first_name (optional)      | string/null         | 255 chars | Fist name of the subscriber.                                                                                                                                                  |
| last_name (optional)       | string/null         | 255 chars | Last name of the subscriber.                                                                                                                                                  |
| cf\_\* (optional/required) | string/boolean/null | 65K chars | A custom field (see [Get Subscriber Fields](GetSubscriberFields.md)). <br> If a custom field is a checkbox, send truthy or falsy value (`true`/`false, `1`/`0`or`"1"`\`"0"`). |

### `$list_ids` (optional)

An array containing list ids into which subscriber will be added.
In case that the list is empty a subscriber will be created; but sending a confirmation email, notification email and scheduling welcome email will be skipped.

### `$options` (optional)

All options are optional. If omitted a default value is used.

| Option                       | Type    | Default | Description                                                                                                                                                                                                                                                                                                            |
| ---------------------------- | ------- | ------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| send_confirmation_email      | boolean | true    | Can be used to disable a confirmation email. Otherwise, a confirmation email is sent as described above. It is strongly recommended to keep this option set to `true` so that MailPoet settings for sign-up confirmation are respected. Turning it to `false` might lead that subscriber to be added as `unconfirmed`. |
| schedule_welcome_email       | boolean | true    | Can be used to disable a welcome email. Otherwise, a welcome email is scheduled as described above.                                                                                                                                                                                                                    |
| skip_subscriber_notification | boolean | false   | Can be used to disable an admin notification email. Otherwise, an admin notification email is sent as described above.                                                                                                                                                                                                 |

## Error handling

All expected errors from the API are exceptions of class `\MailPoet\API\MP\v1\APIException`.
Code of the exception is populated to distinguish between different errors.

An exception of base class `\Exception` can be thrown when something unexpected happens.

Codes description:

| Code | Description                                        |
| ---- | -------------------------------------------------- |
| 11   | Missing email address                              |
| 12   | Trying to create a subscriber that already exists  |
| 13   | The subscriber couldn’t be created in the database |
| 17   | Welcome email failed to send                       |
