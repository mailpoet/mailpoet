[back to list](../Readme.md)

# Delete List

## `bool deleteList(string $list_id)`

This method provides functionality for deleting a new list.

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
