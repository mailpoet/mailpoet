[back to list](../Readme.md)

# Is setup complete

## `bool isSetupComplete()`

This method checks if the MailPoet is set up.

It returns `false` if any of the post-install onboarding screens would show up on the next MailPoet page visit:

- the Welcome Wizard,
- the WooCommerce list-import page,
- the revenue tracking permission page.

Otherwise it returns `true`.
