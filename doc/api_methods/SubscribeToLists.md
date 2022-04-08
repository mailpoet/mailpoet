[back to list](../Readme.md)

# Subscribe to Lists

## `array subscribeToLists(string $subscriber_id, array $list_ids [, array $options = []])`

This method allows adding an existing subscriber into lists and handles confirmation email and admin notification email sending.

- _A confirmation email_ is an email which is sent to a subscriber so they can confirm their subscription. It is sent only if sign-up confirmation is enabled in MailPoet settings and subscriber has not received any confirmation email yet.
- _A welcome email_ is an automatic email which is sent to a new subscriber. This email is scheduled only if sign-up confirmation is disabled and some welcome email is configured for some of given lists.
- _An admin notification email_ is sent to the site admin to inform them about a new subscription. It is sent only if the notification feature is enabled in the MailPoet setting.

All these emails can be disabled using `$options`.

It returns a subscriber. See [Get Subscriber](GetSubscriber.md) for a subscriber data structure.

## Arguments

### string `$subscriber_id` (required)

An id or an email address. An `\Exception` is thrown when the value doesn't match any subscriber.

### array `$list_ids` (required)

An array of list ids. An `\Exception` is thrown if any of list ids are invalid. In such a case the subscriber isn't added to any list.

### array `$options` (optional)

All options are optional. If omitted, a default value is used.

| Option                       | Type    | Default | Description                                                                                                            |
| ---------------------------- | ------- | ------- | ---------------------------------------------------------------------------------------------------------------------- |
| send_confirmation_email      | boolean | true    | Can be used to disable confirmation email. Otherwise, a confirmation email is sent as described above.                 |
| schedule_welcome_email       | boolean | true    | Can be used to disable welcome email. Otherwise, a welcome email is scheduled as described above.                      |
| skip_subscriber_notification | boolean | false   | Can be used to disable an admin notification email. Otherwise, an admin notification email is sent as described above. |

## Error handling

All expected errors from the API are exceptions of class `\MailPoet\API\MP\v1\APIException`.
Code of the exception is populated to distinguish between different errors.

An exception of base class `\Exception` can be thrown when something unexpected happens.

Codes description:

| Code | Description                                             |
| ---- | ------------------------------------------------------- |
| 3    | No lists provided                                       |
| 4    | Invalid subscriber that does not exist                  |
| 5    | Invalid list that does not exist                        |
| 6    | Trying to subscribe to a WordPress Users list           |
| 7    | Trying to subscribe to a WooCommerce Customers list     |
| 8    | Trying to subscribe to a list that doesn’t support that |
| 10   | Confirmation email failed to send                       |
| 17   | Welcome email failed to send                            |
