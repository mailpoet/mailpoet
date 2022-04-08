[back to list](../Readme.md)

# Add Subscriber Field

## `array addSubscriberField(array $data)`

Using this method you can create custom properties that can be used for storing additional data for each subscriber.
See [Subscriber Fields for more details](GetSubscriberFields.md)

## Arguments

### `$data` (required)

| Property          | Type   | Limits   | Description                                                                                       |
| ----------------- | ------ | -------- | ------------------------------------------------------------------------------------------------- |
| name (required)   | string | 90 chars | Human readable name. Intended to be used, as an example, as a label for form input.               |
| type (required)   | string | -        | Type of the field. Possible values are: `text`, `date`, `textarea`, `radio`, `checkbox`, `select` |
| params (optional) | array  | -        | Contains various information, see examples below.                                                 |

### `$params`

Params array differs for each type.
The common properties for all types:

| Property | Type   | Description                                                                                 |
| -------- | ------ | ------------------------------------------------------------------------------------------- |
| required | string | Indicates if the value must be provided for each subscriber. Possible values are: "1" or "" |
| label    | string | Label used for displaying the field to the end user.                                        |

#### `$params` for text, textarea types

| Property | Type   | Description                                                                                 |
| -------- | ------ | ------------------------------------------------------------------------------------------- |
| validate | string | Can be used for validating input values. Possible values are: `number`, `alphanum`, `phone` |

#### `$params` for checkbox types

| Property | Type  | Description                                                  |
| -------- | ----- | ------------------------------------------------------------ |
| values   | array | Same array as for radio type. Must contain exactly 1 element |

#### `$params` for radio, select types

| Property | Type  | Description                                                                                         |
| -------- | ----- | --------------------------------------------------------------------------------------------------- |
| values   | array | Contains a list of options. Each element must contain a string `value` and can contain `is_checked` |

#### `$params` for date type

| Property    | Type   | Description                                                                                                                                 |
| ----------- | ------ | ------------------------------------------------------------------------------------------------------------------------------------------- |
| date_type   | string | Possible values are: Values: `year_month_day`, `year_month`, `month`, `day`                                                                 |
| date_format | string | Values: for year_month_day: `MM/DD/YYYY`, `DD/MM/YYYY`, `YYYY/MM/DD`, for year_month: `YYYY/MM`, `MM/YY`, for year: `YYYY`, for month: `MM` |

## Response

| Property   | Type         | Limits   | Description                                                                                       |
| ---------- | ------------ | -------- | ------------------------------------------------------------------------------------------------- |
| id         | string       | 11 chars | Field Id                                                                                          |
| name       | string       | 90 chars | Human readable name. Intended to be used, as an example, as a label for form input.               |
| type       | string       | -        | Type of the field. Possible values are: `text`, `date`, `textarea`, `radio`, `checkbox`, `select` |
| params     | array        | -        | Contains various information, see examples below.                                                 |
| created_at | string\|null | -        | UTC time of creation in `Y-m-d H:i:s` format                                                      |
| updated_at | string       | -        | UTC time of last update in `Y-m-d H:i:s` format                                                   |

## Error handling

All expected errors from the API are exceptions of class `\MailPoet\API\MP\v1\APIException`.
Code of the exception is populated to distinguish between different errors.

An exception of base class `\Exception` can be thrown when something unexpected happens.

Codes description:

| Code | Description                                                                        |
| ---- | ---------------------------------------------------------------------------------- |
| 1    | The subscriber couldn’t be created in the database                                 |
| 1001 | Missing a mandatory field in the `$data` argument                                  |
| 1002 | A mandatory field in the `$data` argument has wrong type                           |
| 1003 | `$params` is not an array                                                          |
| 1004 | Attempting to create a field with an unknown type                                  |
| 1005 | Incorrect validate parameter for text type                                         |
| 1006 | Passing a `values` array for the checkbox type that has incorrect number of values |
| 1007 | Incorrect `date_format` value                                                      |
| 1008 | Incorrect `date_type` value                                                        |
| 1009 | Missing `values` for select or radio types                                         |
| 1010 | Empty `value` for select or radio types                                            |
