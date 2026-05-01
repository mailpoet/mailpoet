[back to list](../Readme.md)

# Get Tags

## `array getTags()`

In MailPoet, tags can be attached to subscribers to label them. This method returns an array of all available tags.

### A tag data structure

| Property    | Type         | Limits    | Description                                     |
| ----------- | ------------ | --------- | ----------------------------------------------- |
| id          | string       | 11 chars  | Id of the tag                                   |
| name        | string       | 191 chars | Name of the tag                                 |
| description | string       | -         | Description of the tag                          |
| created_at  | string\|null | -         | UTC time of creation in 'Y-m-d H:i:s' format    |
| updated_at  | string\|null | -         | UTC time of last update in 'Y-m-d H:i:s' format |

### Response Example

```php
<?php
[
  0 => [
    'id' => '1',
    'name' => 'VIP',
    'description' => 'Important customers',
    'created_at' => '2026-04-21 07:24:37',
    'updated_at' => '2026-04-21 07:24:37',
  ],
  1 => [
    'id' => '2',
    'name' => 'Newsletter',
    'description' => '',
    'created_at' => '2026-04-21 11:38:46',
    'updated_at' => '2026-04-21 11:41:25',
  ],
]
```

## Example

```php
<?php

if (class_exists(\MailPoet\API\API::class)) {
  $mailpoet_api = \MailPoet\API\API::MP('v1');

  $tags = $mailpoet_api->getTags();
  // Build a name => id map for use with tagSubscriber()
  $tags_by_name = array_column($tags, 'id', 'name');
}
```
