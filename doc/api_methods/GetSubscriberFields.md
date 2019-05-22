[back to list](../Readme.md)

# Get Subscriber fields

## `array getSubscriberFields()`

Subscriber has a set of default properties (`email`, `first_name`, `last_name`).
MailPoet enables to extend these properties by adding custom properties.
This method returns list of all properties available for a subscriber (default + custom).

## Subscriber Field

| Property | Type | Limits | Description |
| --- | --- | --- | --- |
| id | string | 11 chars |Field Id |
| name | string | 90 chars | Human readable name. Intended to be used e.g. as a label for form input. |

## Response Example
```php
<?php
[
  0 => [
    'id' => 'email',
    'name' => 'Email',
  ],
  1 => [
    'id' => 'first_name',
    'name' => 'First name',
  ],
  2 => [
    'id' => 'last_name',
    'name' => 'Last name',
  ],
  3 => [
    'id' => 'cf_country',
    'name' => 'Country',
  ],
]

```
