[back to list](../Readme.md)

# Get Tag

## `array getTag($tagIdOrName)`

Looks up a single tag by id or name. Returns the tag. See [Get Tags](GetTags.md) for a tag data structure description.

## Arguments

| Argument     | Type          | Description                              |
| ------------ | ------------- | ---------------------------------------- |
| $tagIdOrName | int or string | Id of an existing tag or its exact name. |

A numeric string is treated as an id. Any other string is treated as a tag name and must match an existing tag exactly.

## Error handling

All expected errors from the API are exceptions of class `\MailPoet\API\MP\v1\APIException`.
Code of the exception is populated to distinguish between different errors.

An exception of base class `\Exception` can be thrown when something unexpected happens.

Codes description:

| Code | Description                           |
| ---- | ------------------------------------- |
| 29   | Asking for a tag that does not exist. |

## Example

```php
<?php

if (class_exists(\MailPoet\API\API::class)) {
  $mailpoet_api = \MailPoet\API\API::MP('v1');

  try {
    // By id
    $tag = $mailpoet_api->getTag(7);
    // ...or by exact name (case sensitivity follows the database collation)
    $tag = $mailpoet_api->getTag('VIP');
  } catch (\MailPoet\API\MP\v1\APIException $e) {
    if ($e->getCode() === 29 /* TAG_NOT_EXISTS */) {
      $tag = null;
    } else {
      throw $e;
    }
  }
}
```
