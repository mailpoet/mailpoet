[back to list](../Readme.md)

# Add Tag

## `array addTag(array $tag)`

This method provides functionality for creating a new tag.

It returns the new tag. See [Get Tags](GetTags.md) for a tag data structure description.

## Arguments

### `$tag` (required)

An associative array which contains tag data.

| Property               | Type   | Limits    | Description               |
| ---------------------- | ------ | --------- | ------------------------- |
| name (required)        | string | 191 chars | A name of the tag.        |
| description (optional) | string | -         | A description of the tag. |

## Error handling

All expected errors from the API are exceptions of class `\MailPoet\API\MP\v1\APIException`.
Code of the exception is populated to distinguish between different errors.

An exception of base class `\Exception` can be thrown when something unexpected happens.

Codes description:

| Code | Description                                 |
| ---- | ------------------------------------------- |
| 25   | Missing tag name                            |
| 26   | Trying to create a tag that already exists  |
| 27   | The tag couldn’t be created in the database |

## Example

```php
<?php

if (class_exists(\MailPoet\API\API::class)) {
  $mailpoet_api = \MailPoet\API\API::MP('v1');

  try {
    $tag = $mailpoet_api->addTag([
      'name' => 'VIP',
      'description' => 'High-value customers',
    ]);
    // $tag['id'] is the new tag id (string)
  } catch (\MailPoet\API\MP\v1\APIException $e) {
    error_log(sprintf('MailPoet addTag failed [%d]: %s', $e->getCode(), $e->getMessage()));
  }
}
```
