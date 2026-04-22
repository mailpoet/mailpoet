[back to list](../Readme.md)

# Update Tag

## `array updateTag(array $tag)`

This method provides functionality for updating a tag name or description.

It returns the updated tag. See [Get Tags](GetTags.md) for a tag data structure description.

## Arguments

### `$tag` (required)

An associative array which contains tag data. Only the `id` is required; `name` and `description` are both optional and only update when the key is present, so you can update one without affecting the other.

| Property               | Type   | Limits    | Description                                                                                               |
| ---------------------- | ------ | --------- | --------------------------------------------------------------------------------------------------------- |
| id (required)          | string | 11 chars  | An id of the tag.                                                                                         |
| name (optional)        | string | 191 chars | A new name for the tag. Omit the key to keep the existing name.                                           |
| description (optional) | string | -         | A new description for the tag. Omit the key to keep the existing value; pass an empty string to clear it. |

## Error handling

All expected errors from the API are exceptions of class `\MailPoet\API\MP\v1\APIException`.
Code of the exception is populated to distinguish between different errors.

An exception of base class `\Exception` can be thrown when something unexpected happens.

Codes description:

| Code | Description                                          |
| ---- | ---------------------------------------------------- |
| 25   | Missing tag name (when `name` is provided but empty) |
| 26   | Trying to use a tag name that is already used        |
| 28   | Missing tag id                                       |
| 29   | The tag was not found by id                          |
| 30   | The tag couldn’t be updated in the database          |
