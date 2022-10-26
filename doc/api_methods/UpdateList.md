[back to list](../Readme.md)

# Add Subscriber

## `array updateList(array $list)`

This method provides functionality for updating a list name or description.

It returns the updated list. See [Get Lists](GetLists.md) for a list data structure description.

## Arguments

### `$list` (required)

An associative array which contains list data.

| Property               | Type         | Limits    | Description                |
| ---------------------- | ------------ | --------- | -------------------------- |
| id (required)          | string       | 11 chars  | A id of the list.          |
| name (required)        | string       | 90 chars  | A name of the list.        |
| description (optional) | string\|null | 250 chars | A description of the list. |

## Error handling

All expected errors from the API are exceptions of class `\MailPoet\API\MP\v1\APIException`.
Code of the exception is populated to distinguish between different errors.

An exception of base class `\Exception` can be thrown when something unexpected happens.

Codes description:

| Code | Description                                  |
| ---- | -------------------------------------------- |
| 5    | The list was not found by id                 |
| 14   | Missing list name                            |
| 15   | Trying to use a list that is already used    |
| 18   | Missing list id                              |
| 19   | The list couldn’t be updated in the database |
