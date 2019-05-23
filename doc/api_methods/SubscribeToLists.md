[back to list](../Readme.md)

# Subscribe to Lists

## `array subscribeToLists(string $subscriber_id, array $list_ids [, array $options = []])`

This method allows adding an existing subscriber into lists and handles confirmation email and admin notification email sending.

*A confirmation email* is an email which is sent to a subscriber so they can confirm their subscription. It is sent only if sign-up confirmation is enabled in MailPoet settings and subscriber has not received any confirmation email yet.
*A welcome email* is an automatic email which is sent to a new subscriber. This email is scheduled only if sign-up confirmation is disabled and some welcome email is configured for some of given lists.

It returns a subscriber. See [Get Subscriber](GetSubscriber.md) for a subscriber data structure.

## Arguments
### string `$subscriber_id` (required)
An id or an email address. An `\Exception` is thrown when the value doesn't match any subscriber.

### array `$list_ids` (required)
An array of list ids. An `\Exception` is thrown if any of list ids are invalid. In such a case the subscriber isn't added to any list.

### array `$options` (optional)
All options are optional. If omitted, a default value is used.

| Option | Type | Default | Description |
| --- | --- | --- | --- |
| send_confirmation_email | boolean | true | Can be used to disable confirmation email. Otherwise, a confirmation email is sent as described above.|
| schedule_welcome_email | boolean | true | Can be used to disable welcome email. Otherwise, a welcome email is scheduled as described above.|
