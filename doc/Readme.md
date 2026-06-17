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
add_action('init', function () {
  if (!class_exists(\MailPoet\API\API::class)) {
    return;
  }
  $mailpoet_api = \MailPoet\API\API::MP('v1');
});
```

Class `\MailPoet\API\API` becomes available once MailPoet plugin is loaded by WordPress. Call the API from `init` (default priority) or later so MailPoet has finished bootstrapping and run any pending database migrations.

### Available API Methods

#### Setup

- [Is Setup Complete (isSetupComplete)](api_methods/IsSetupComplete.md)

#### Lists

- [Get Lists (getLists)](api_methods/GetLists.md)
- [Add List (addList)](api_methods/AddList.md)
- [Update List (updateList)](api_methods/UpdateList.md)
- [Delete List (deleteList)](api_methods/DeleteList.md)

#### Subscribers

- [Get Subscriber (getSubscriber)](api_methods/GetSubscriber.md)
- [Get Subscribers (getSubscribers)](api_methods/GetSubscribers.md)
- [Get Subscribers Count (getSubscribersCount)](api_methods/GetSubscribersCount.md)
- [Add Subscriber (addSubscriber)](api_methods/AddSubscriber.md)
- [Update Subscriber (updateSubscriber)](api_methods/UpdateSubscriber.md)
- [Subscribe to List (subscribeToList)](api_methods/SubscribeToList.md)
- [Subscribe to Lists (subscribeToLists)](api_methods/SubscribeToLists.md)
- [Unsubscribe from List (unsubscribeFromList)](api_methods/UnsubscribeFromList.md)
- [Unsubscribe from Lists (unsubscribeFromLists)](api_methods/UnsubscribeFromLists.md)
- [Unsubscribe globally (unsubscribe)](api_methods/UnsubscribeGlobally.md)

#### Subscriber Fields

- [Get Subscriber Fields (getSubscriberFields)](api_methods/GetSubscriberFields.md)
- [Add Subscriber Field (addSubscriberField)](api_methods/AddSubscriberField.md)

#### Tags

- [Get Tag (getTag)](api_methods/GetTag.md)
- [Get Tags (getTags)](api_methods/GetTags.md)
- [Add Tag (addTag)](api_methods/AddTag.md)
- [Update Tag (updateTag)](api_methods/UpdateTag.md)
- [Delete Tag (deleteTag)](api_methods/DeleteTag.md)
- [Tag Subscriber (tagSubscriber)](api_methods/TagSubscriber.md)
- [Untag Subscriber (untagSubscriber)](api_methods/UntagSubscriber.md)

### Usage examples

You can check some basic examples [here](UsageExamples.md).

## WP-CLI commands

MailPoet ships [WP-CLI](https://wp-cli.org/) commands for inspecting and running its background (cron)
tasks from the command line — useful for debugging a site or running the queue from system cron.

- [`wp mailpoet cron` cron task commands](wp-cli-cron-commands.md)
