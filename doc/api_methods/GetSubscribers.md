[back to list](../Readme.md)

# Get Subscribers

## `array getSubscribers(array $filter = [], int $limit = 50, int $offset = 0)`

This method returns a list of subscribers. To see the subscriber data structure, please check [getSubscriber()](GetSubscriber.md) documentation.

## Arguments

| Argument           | Type  | Default | Description                             |
| ------------------ | ----- | ------- | --------------------------------------- |
| $filter (optional) | array | empty   | Filters to retrieve subscribers         |
| $limit (optional)  | int   | 50      | The number of results that are returned |
| $offset (optional) | int   | 0       | From where to start returning data      |

### Filter

Filter argument supports following array keys.

| Key            | Type         | Description                                                                                                       |
| -------------- | ------------ | ----------------------------------------------------------------------------------------------------------------- |
| status         | string       | Specific status of subscribers. One of values: `unconfirmed`, `subscribed`, `unsubscribed`, `bounced`, `inactive` |
| list_id        | int          | List id or dynamic segment id                                                                                     |
| min_updated_at | DateTime\int | DateTime object or timestamp of the minimal last update of subscribers                                            |
