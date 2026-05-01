[back to list](../Readme.md)

# Get Subscriber fields

## `array getSubscriberFields()`

Each subscriber has a set of default properties (`email`, `first_name`, `last_name`).
MailPoet enables the extension of these properties by adding custom properties.
This method returns list of all properties available for a subscriber (default + custom).
See also [addSubscriberField function.](AddSubscriberField.md)

## Subscriber Field

| Property | Type   | Limits   | Description                                                                                       |
| -------- | ------ | -------- | ------------------------------------------------------------------------------------------------- |
| id       | string | 11 chars | Field Id                                                                                          |
| name     | string | 90 chars | Human readable name. Intended to be used, as an example, as a label for form input.               |
| type     | string | -        | Type of the field. Possible values are: `text`, `date`, `textarea`, `radio`, `checkbox`, `select` |
| params   | array  | -        | Contains various information, see examples below.                                                 |

## Response Example

```php
<?php
[
  0 => [
    'id' => 'email',
    'name' => 'Email',
    'type' => 'text',
    'params' => [
      'required' => '1',
    ],
  ],
  1 => [
    'id' => 'first_name',
    'name' => 'First name',
    'type' => 'text',
    'params' => [
      'required' => '',
    ],
  ],
  2 => [
    'id' => 'last_name',
    'name' => 'Last name',
    'type' => 'text',
    'params' => [
      'required' => '',
    ],
  ],
  3 => [
   'id' => 'cf_1',
   'type' => 'radio', // values: radio, select
   'name' => 'Radio or select input',
   'params' => [
     'values' => [
       0 => [
         'value' => 'value 1',
       ],
       1 => [
         'is_checked' => '1',
         'value' => 'value 2',
       ],
     ],
     'required' => '1',
    ],
  ],
  4 => [
    'id' => 'cf_2', // Text, textarea, email
    'type' => 'textarea', // values: text, textarea
    'name' => 'Text or text area input',
    'params' => [
      'required' => '1',
      'label' => 'Text field label',
      'validate' => '', // number, alphanum, phone
    ],
  ],
  5 => [
    'id' => 'cf_3',
    'type' => 'date',
    'name' => 'Date field',
    'params' => [
      'required' => '',
      'date_type' => 'year_month_day', // Values: year_month_day, year_month, month, day
      'date_format' => 'MM/DD/YYYY', // Values: for year_month_day: 'MM/DD/YYYY', 'DD/MM/YYYY', 'YYYY/MM/DD', for year_month: 'YYYY/MM', 'MM/YY', for year: 'YYYY', for month: 'MM'
    ],
  ],
  6 => [
    'id' => 'cf_4',
    'type' => 'checkbox',
    'name' => 'Checkbox',
    'params' => [
      'values' => [ // Checkbox accepts only one value in values
        0 => [
          'is_checked' => '1',
          'value' => 'checkbox value',
        ],
      ],
      'required' => '1',
    ],
  ]
]

```

## Example

```php
<?php

if (class_exists(\MailPoet\API\API::class)) {
  $mailpoet_api = \MailPoet\API\API::MP('v1');

  $fields = $mailpoet_api->getSubscriberFields();

  // Render a form with the required fields, skipping the default email field
  // which is always required and is typically already wired up.
  foreach ($fields as $field) {
    if ($field['id'] === 'email') {
      continue;
    }
    $required = !empty($field['params']['required']);
    printf(
      '<label>%s%s<input name="%s" type="%s"></label>',
      esc_html($field['name']),
      $required ? ' *' : '',
      esc_attr($field['id']),
      esc_attr(in_array($field['type'], ['text', 'date'], true) ? $field['type'] : 'text')
    );
  }
}
```
