[back to list](../Readme.md)

# Add Subscriber

## `array addList(array $list)`

In MailPoet, subscribers are organized into lists. This method provides functionality for creating a new list.

It returns the new list. See [Get Lists](GetLists.md) for a list data structure description.

## Arguments
### `$list` (required)

An associative array which contains list data.

| Property | Type | Limits | Description |
| --- | --- | --- | --- |
| name (required) | string | 90 chars | A name of the list. |
| description (optional) | string\|null| 250 chars | A description of the list. |
