[back to list](../Readme.md)

# Is setup complete

## `bool isSetupComplete()`

This method checks if the MailPoet is set up.

It returns `false` if any of the post-install onboarding screens would show up on the next MailPoet page visit:

- the Welcome Wizard,
- the WooCommerce list-import page,
- the revenue tracking permission page.

Otherwise it returns `true`.

## Example

```php
<?php

if (class_exists(\MailPoet\API\API::class)) {
  $mailpoet_api = \MailPoet\API\API::MP('v1');

  if (!$mailpoet_api->isSetupComplete()) {
    // The site admin still has post-install onboarding to do; defer any
    // calls that would fail or behave unexpectedly until setup is finished.
    return;
  }
}
```
