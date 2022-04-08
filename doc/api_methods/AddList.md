[back to list](../Readme.md)

# Add Subscriber

## `array addList(array $list)`

In MailPoet, subscribers are organized into lists. This method provides functionality for creating a new list.

It returns the new list. See [Get Lists](GetLists.md) for a list data structure description.

## Arguments

### `$list` (required)

An associative array which contains list data.

| Property               | Type         | Limits    | Description                |
| ---------------------- | ------------ | --------- | -------------------------- |
| name (required)        | string       | 90 chars  | A name of the list.        |
| description (optional) | string\|null | 250 chars | A description of the list. |

## Error handling

All expected errors from the API are exceptions of class `\MailPoet\API\MP\v1\APIException`.
Code of the exception is populated to distinguish between different errors.

An exception of base class `\Exception` can be thrown when something unexpected happens.

Codes description:

| Code | Description                                  |
| ---- | -------------------------------------------- |
| 14   | Missing list name                            |
| 15   | Trying to create a list that already exists  |
| 16   | The list couldn’t be created in the database |
