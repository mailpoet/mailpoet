<?php
namespace MailPoet\Subscribers\ImportExport\Import;

use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\BootStrapMenu;
use MailPoet\Util\Helpers;

class Import {
  public $subscribers_data;
  public $segments;
  public $update_subscribers;
  public $subscriber_fields;
  public $subscriber_custom_fields;
  public $subscribers_count;
  public $created_at;
  public $updated_at;
  public $profiler_start;

  public function __construct($data) {
    $this->subscribers_data = $this->transformSubscribersData(
      $data['subscribers'],
      $data['columns']
    );
    $this->segments = $data['segments'];
    $this->update_subscribers = $data['updateSubscribers'];
    $this->subscriber_fields = $this->getSubscriberFields(
      array_keys($data['columns'])
    );
    $this->subscriber_custom_fields = $this->getCustomSubscriberFields(
      array_keys($data['columns'])
    );
    $this->subscribers_count = count(reset($this->subscribers_data));
    $this->created_at = date('Y-m-d H:i:s', (int) $data['timestamp']);
    $this->updated_at = date('Y-m-d H:i:s', (int) $data['timestamp'] + 1);
    $this->profiler_start = microtime(true);
  }

  function process() {
    $subscriber_fields = $this->subscriber_fields;
    $subscriber_custom_fields = $this->subscriber_custom_fields;
    $subscribers_data = $this->subscribers_data;
    list ($subscribers_data, $subscriber_fields) =
      $this->filterSubscriberStatus($subscribers_data, $subscriber_fields);
    $this->deleteExistingTrashedSubscribers($subscribers_data);
    list($subscribers_data, $subscriber_fields) = $this->extendSubscribersAndFields(
      $subscribers_data, $subscriber_fields
    );
    list($existing_subscribers, $new_subscribers) =
      $this->filterExistingAndNewSubscribers($subscribers_data);
    $created_subscribers = $updated_subscribers = array();
    try {
      if($new_subscribers) {
        $created_subscribers =
          $this->createOrUpdateSubscribers(
            'create',
            $new_subscribers,
            $subscriber_fields,
            $subscriber_custom_fields
          );
      }
      if($existing_subscribers && $this->update_subscribers) {
        $updated_subscribers =
          $this->createOrUpdateSubscribers(
            'update',
            $existing_subscribers,
            $subscriber_fields,
            $subscriber_custom_fields
          );
      }
    } catch(\PDOException $e) {
      return array(
        'result' => false,
        'errors' => array($e->getMessage())
      );
    }
    $segments = new BootStrapMenu('import');
    return array(
      'result' => true,
      'data' => array(
        'created' => count($created_subscribers),
        'updated' => count($updated_subscribers),
        'segments' => $segments->getSegments()
      ),
      'profiler' => $this->timeExecution()
    );
  }

  function transformSubscribersData($subscribers, $columns) {
    foreach($columns as $column => $index) {
      $transformed_subscribers[$column] = Helpers::arrayColumn($subscribers, $index);
    }
    return $transformed_subscribers;
  }

  function filterExistingAndNewSubscribers($subscribers_data) {
    $chunk_size = 200;
    $existing_records = array_filter(
      array_map(function($subscriber_emails) {
        return Subscriber::selectMany(array('email'))
          ->whereIn('email', $subscriber_emails)
          ->whereNull('deleted_at')
          ->findArray();
      }, array_chunk($subscribers_data['email'], $chunk_size))
    );
    if(!$existing_records) {
      return array(
        false,
        $subscribers_data
      );
    }
    $existing_records = Helpers::flattenArray($existing_records);
    $new_records = array_keys(
      array_diff(
        $subscribers_data['email'],
        $existing_records
      )
    );
    if(!$new_records) {
      return array(
        $subscribers_data,
        false
      );
    }
    $new_subscribers =
      array_filter(
        array_map(function($subscriber) use ($new_records) {
          return array_map(function($index) use ($subscriber) {
            return $subscriber[$index];
          }, $new_records);
        }, $subscribers_data)
      );

    $existing_subscribers =
      array_map(function($subscriber) use ($new_records) {
        return array_values( // reindex array
          array_filter( // remove NULL entries
            array_map(function($index, $data) use ($new_records) {
              if(!in_array($index, $new_records)) return $data;
            }, array_keys($subscriber), $subscriber)
          )
        );
      }, $subscribers_data);
    return array(
      $existing_subscribers,
      $new_subscribers
    );
  }

  function deleteExistingTrashedSubscribers($subscribers_data) {
    $chunk_size = 200;
    $existing_trashed_records = array_filter(
      array_map(function($subscriber_emails) {
        return Subscriber::selectMany(array('id'))
          ->whereIn('email', $subscriber_emails)
          ->whereNotNull('deleted_at')
          ->findArray();
      }, array_chunk($subscribers_data['email'], $chunk_size))
    );
    if(!$existing_trashed_records) return;
    $existing_trashed_records = Helpers::flattenArray($existing_trashed_records);
    foreach(array_chunk($existing_trashed_records, $chunk_size) as
            $subscriber_ids) {
      Subscriber::whereIn('id', $subscriber_ids)
        ->deleteMany();
      SubscriberSegment::whereIn('subscriber_id', $subscriber_ids)
        ->deleteMany();
    }
  }

  function extendSubscribersAndFields($subscribers_data, $subscriber_fields) {
    $subscribers_data['created_at'] =
      array_fill(0, $this->subscribers_count, $this->created_at);
    $subscriber_fields[] = 'created_at';
    return array(
      $subscribers_data,
      $subscriber_fields
    );
  }

  function getSubscriberFields($subscriber_fields) {
    return array_values(
      array_filter(
        array_map(function($field) {
          if(!is_int($field)) return $field;
        }, $subscriber_fields)
      )
    );
  }

  function getCustomSubscriberFields($subscriber_fields) {
    return array_values(
      array_filter(
        array_map(function($field) {
          if(is_int($field)) return $field;
        }, $subscriber_fields)
      )
    );
  }

  function filterSubscriberStatus($subscribers_data, $subscriber_fields) {
    if(!in_array('status', $subscriber_fields)) {
      $subscribers_data['status'] =
        array_fill(0, count($subscribers_data['email']), 'subscribed');
      $subscriber_fields[] = 'status';
      return array(
        $subscribers_data,
        $subscriber_fields
      );
    }
    $statuses = array(
      'subscribed' => array(
        'subscribed',
        'confirmed',
        1,
        '1',
        'true'
      ),
      'unconfirmed' => array(
        'unconfirmed',
        0,
        "0"
      ),
      'unsubscribed' => array(
        'unsubscribed',
        -1,
        '-1',
        'false'
      )
    );
    $subscribers_data['status'] = array_map(function($state) use ($statuses) {
      if(in_array(strtolower($state), $statuses['subscribed'])) {
        return 'subscribed';
      }
      if(in_array(strtolower($state), $statuses['unsubscribed'])) {
        return 'unsubscribed';
      }
      if(in_array(strtolower($state), $statuses['unconfirmed'])) {
        return 'unconfirmed';
      }
      return 'subscribed'; // make "subscribed" a default status
    }, $subscribers_data['status']);
    return array(
      $subscribers_data,
      $subscriber_fields
    );
  }

  function createOrUpdateSubscribers(
    $action,
    $subscribers_data,
    $subscriber_fields,
    $subscriber_custom_fields
  ) {
    $chunk_size = 100;
    $subscribers_count = count(reset($subscribers_data)) - 1;
    $subscribers = array_map(function($index) use ($subscribers_data, $subscriber_fields) {
      return array_map(function($field) use ($index, $subscribers_data) {
        return $subscribers_data[$field][$index];
      }, $subscriber_fields);
    }, range(0, $subscribers_count));
    foreach(array_chunk($subscribers, $chunk_size) as $data) {
      if($action == 'create') {
        Subscriber::createMultiple(
          $subscriber_fields,
          $data
        );
      }
      if($action == 'update') {
        Subscriber::updateMultiple(
          $subscriber_fields,
          $data,
          $this->updated_at
        );
      }
    }
    $query = Subscriber::selectMany(
      array(
        'id',
        'email'
      ));
    $query = ($action === 'update') ?
      $query->where('updated_at', $this->updated_at) :
      $query->where('created_at', $this->created_at);
    $result = Helpers::arrayColumn(
      $query->findArray(),
      'email', 'id'
    );
    if($subscriber_custom_fields) {
      $this->createOrUpdateCustomFields(
        ($action === 'create') ? 'create' : 'update',
        $result,
        $subscribers_data,
        $subscriber_custom_fields
      );
    }
    $this->addSubscribersToSegments(
      array_keys($result),
      $this->segments
    );
    return $result;
  }

  function createOrUpdateCustomFields(
    $action,
    $db_subscribers,
    $subscribers_data,
    $subscriber_custom_fields
  ) {
    $subscribers = array_map(
      function($column) use ($db_subscribers, $subscribers_data) {
        $count = range(0, count($subscribers_data[$column]) - 1);
        return array_map(
          function($index, $value)
          use ($db_subscribers, $subscribers_data, $column) {
            $subscriber_id = array_search(
              $subscribers_data['email'][$index],
              $db_subscribers
            );
            return array(
              $column,
              $subscriber_id,
              $value
            );
          }, $count, $subscribers_data[$column]);
      }, $subscriber_custom_fields);
    foreach(array_chunk($subscribers[0], 200) as $data) {
      if($action === 'create') {
        SubscriberCustomField::createMultiple(
          $data
        );
      }
      if($action === 'update') {
        SubscriberCustomField::updateMultiple(
          $data
        );
      }
    }
  }

  function addSubscribersToSegments($subscribers, $segments) {
    foreach(array_chunk($subscribers, 200) as $data) {
      SubscriberSegment::createMultiple($segments, $data);
    }
  }

  function timeExecution() {
    $profiler_end = microtime(true);
    return ($profiler_end - $this->profiler_start) / 60;
  }
}