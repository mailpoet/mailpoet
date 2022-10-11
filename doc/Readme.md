# MailPoet – Documentation for Integrators

This is a place where we put documentation for developers who want to build an extension for MailPoet plugin.
If you are a user looking for a user guide please visit our [knowledge base](https://kb.mailpoet.com/).

## MailPoet API

MailPoet API is the officially supported way to integrate with the MailPoet plugin. It focuses on functionality for managing subscribers.
Developers integrating MailPoet functionality in their own plugins or projects are strongly discouraged against using other functions and classes within MailPoet codebase! We are continually refactoring as part of our rapid development process, and backward compatibility is not guaranteed.

### Basics

MailPoet API is distributed within MailPoet3 plugin and it is implemented as a PHP class.
Currently supported version is `v1`.

### Instantiation

```php
if (class_exists(\MailPoet\API\API::class)) {
  $mailpoet_api = \MailPoet\API\API::MP('v1');
}
```

Class `\MailPoet\API\API` becomes available once MailPoet plugin is loaded by WordPress.

### Available API Methods

- [Add List (addList)](api_methods/AddList.md)
- [Add Subscriber (addSubscriber)](api_methods/AddSubscriber.md)
- [Add Subscriber Field (addSubscriberField)](api_methods/AddSubscriberField.md)
- [Get Lists (getLists)](api_methods/GetLists.md)
- [Get Subscriber (getSubscriber)](api_methods/GetSubscriber.md)
- [Get Subscriber Fields (getSubscriberFields)](api_methods/GetSubscriberFields.md)
- [Is Setup Complete (isSetupComplete)](api_methods/IsSetupComplete.md)
- [Subscribe to List (subscribeToList)](api_methods/SubscribeToList.md)
- [Subscribe to Lists (subscribeToLists)](api_methods/SubscribeToLists.md)
- [Unsubscribe from List (unsubscribeFromList)](api_methods/UnsubscribeFromList.md)
- [Unsubscribe from Lists (unsubscribeFromLists)](api_methods/UnsubscribeFromLists.md)

### Usage examples

You can check some basic examples [here](UsageExamples.md).
