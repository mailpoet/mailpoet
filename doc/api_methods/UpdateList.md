[back to list](../Readme.md)

# Update List

## `array updateList(array $list)`

This method provides functionality for updating a list name or description. Only lists of type 'default' are supported.

It returns the updated list. See [Get Lists](GetLists.md) for a list data structure description.

## Arguments

### `$list` (required)

An associative array which contains list data.

| Property               | Type         | Limits    | Description                                                                |
| ---------------------- | ------------ | --------- | -------------------------------------------------------------------------- |
| id (required)          | string       | 11 chars  | A id of the list.                                                          |
| name (required)        | string       | 90 chars  | A name of the list.                                                        |
| description (optional) | string\|null | 250 chars | A description of the list. This will reset the list description when empty |

## Error handling

All expected errors from the API are exceptions of class `\MailPoet\API\MP\v1\APIException`.
Code of the exception is populated to distinguish between different errors.

An exception of base class `\Exception` can be thrown when something unexpected happens.

Codes description:

| Code | Description                                     |
| ---- | ----------------------------------------------- |
| 5    | The list was not found by id                    |
| 14   | Missing list name                               |
| 15   | Trying to use a list name that is already used  |
| 18   | Missing list id                                 |
| 19   | The list couldn’t be updated in the database    |
| 23   | Only lists of the type 'default' can be updated |

## Example

```php
<?php

if (class_exists(\MailPoet\API\API::class)) {
  $mailpoet_api = \MailPoet\API\API::MP('v1');

  try {
    $list = $mailpoet_api->updateList([
      'id' => '5',                       // required
      'name' => 'VIP customers (renamed)',
      'description' => 'High-value customers, hand-picked.',
    ]);
  } catch (\MailPoet\API\MP\v1\APIException $e) {
    error_log(sprintf('MailPoet updateList failed [%d]: %s', $e->getCode(), $e->getMessage()));
  }
}
```
