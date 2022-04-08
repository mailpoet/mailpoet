[back to list](../Readme.md)

# Get Lists

## `array getLists()`

In MailPoet, subscribers are organized into lists. This method returns an array of available lists.

### A list data structure

| Property    | Type         | Limits    | Description                                                                                                 |
| ----------- | ------------ | --------- | ----------------------------------------------------------------------------------------------------------- |
| id          | string       | 11 chars  | Id of the list                                                                                              |
| name        | string       | 90 chars  | Name of the list                                                                                            |
| type        | string       | -         | Type of the list. Currently, there is only one supported value: `default`                                   |
| description | string       | 250 chars | Description of the list                                                                                     |
| created_at  | string\|null | -         | UTC time of creation in 'Y-m-d H:i:s' format                                                                |
| updated_at  | string       | -         | UTC time of last update in 'Y-m-d H:i:s' format                                                             |
| deleted_at  | string\|null | -         | This property is not null only when the list is in the trash. It contains UTC time in 'Y-m-d H:i:s' format. |

### Response Example

```php
<?php
[
  0 => [
    'id' => '3',
    'name' => 'Newsletter mailing list',
    'type' => 'default',
    'description' => 'This list is automatically created when you install MailPoet.',
    'created_at' => '2019-05-07 07:24:37',
    'updated_at' => '2019-05-07 07:24:37',
    'deleted_at' => NULL,
  ],
  1 => [
    'id' => '5',
    'name' => 'Second list',
    'type' => 'default',
    'description' => '',
    'created_at' => '2019-05-15 11:38:46',
    'updated_at' => '2019-05-15 11:41:25',
    'deleted_at' => '2019-05-15 11:41:25',
  ],
]
```
