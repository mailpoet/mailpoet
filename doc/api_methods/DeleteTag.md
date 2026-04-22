[back to list](../Readme.md)

# Delete Tag

## `bool deleteTag(string $tag_id)`

This method provides functionality for deleting a tag. Any existing associations between the tag and subscribers are removed as part of the deletion.

It returns a boolean value.

## Error handling

All expected errors from the API are exceptions of class `\MailPoet\API\MP\v1\APIException`.
Code of the exception is populated to distinguish between different errors.

An exception of base class `\Exception` can be thrown when something unexpected happens.

Codes description:

| Code | Description                                   |
| ---- | --------------------------------------------- |
| 28   | Tag id is empty                               |
| 29   | Tag does not exist                            |
| 31   | The tag couldn’t be deleted from the database |
