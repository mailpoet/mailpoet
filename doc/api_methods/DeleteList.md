[back to list](../Readme.md)

# Delete List

## `bool deleteList(string $list_id)`

This method provides functionality for deleting a list that is of the type 'default'.

It returns a boolean value.

## Error handling

All expected errors from the API are exceptions of class `\MailPoet\API\MP\v1\APIException`.
Code of the exception is populated to distinguish between different errors.

An exception of base class `\Exception` can be thrown when something unexpected happens.

Codes description:

| Code | Description                                                     |
| ---- | --------------------------------------------------------------- |
| 5    | List does not exist                                             |
| 18   | List id is empty                                                |
| 20   | List cannot be deleted because it’s used for an automatic email |
| 21   | List cannot be deleted because it’s used for a form             |
| 22   | The list couldn’t be deleted from the database                  |
| 23   | Only lists of the type 'default' can be deleted                 |

## Example

```php
<?php

if (class_exists(\MailPoet\API\API::class)) {
  $mailpoet_api = \MailPoet\API\API::MP('v1');

  try {
    $mailpoet_api->deleteList('5'); // list id as string
  } catch (\MailPoet\API\MP\v1\APIException $e) {
    // 18 = empty id, 5 = list not found, 20/21 = list still in use,
    // 23 = list type is not 'default' (e.g. dynamic), 22 = delete failed
    error_log(sprintf('MailPoet deleteList failed [%d]: %s', $e->getCode(), $e->getMessage()));
  }
}
```
