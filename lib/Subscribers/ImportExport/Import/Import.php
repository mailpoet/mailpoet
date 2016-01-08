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
  public $import_time;
  public $profiler_start;
  
  public function __construct($data) {
    $this->subscribers_data = $data['subscribers'];
    $this->segments = $data['segments'];
    $this->update_subscribers = $data['updateSubscribers'];
    $this->subscriber_fields = $this->getSubscriberFields(
      array_keys($this->subscribers_data)
    );
    $this->subscriber_custom_fields = $this->getCustomSubscriberFields(
      array_keys($this->subscribers_data)
    );
    $this->subscribers_count = count(reset($this->subscribers_data));
    $this->import_time = date('Y-m-d H:i:s');
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
        if($created_subscribers) {
          // subtract added from updated subscribers when DB operation takes <1s
          $updated_subscribers = array_diff_key(
            $updated_subscribers,
            $created_subscribers,
            $subscriber_custom_fields
          );
        }
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
  
  function filterExistingAndNewSubscribers($subscribers_data) {
    $existing_records = array_filter(
      array_map(function ($subscriber_emails) {
        return Subscriber::selectMany(array('email'))
          ->whereIn('email', $subscriber_emails)
          ->whereNull('deleted_at')
          ->findArray();
      }, array_chunk($subscribers_data['email'], 200))
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
        array_map(function ($subscriber) use ($new_records) {
          return array_map(function ($index) use ($subscriber) {
            return $subscriber[$index];
          }, $new_records);
        }, $subscribers_data)
      );
    
    $existing_subscribers =
      array_map(function ($subscriber) use ($new_records) {
        return array_values( // reindex array
          array_filter( // remove NULL entries
            array_map(function ($index, $data) use ($new_records) {
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
    $existing_trashed_records = array_filter(
      array_map(function ($subscriber_emails) {
        return Subscriber::selectMany(array('id'))
          ->whereIn('email', $subscriber_emails)
          ->whereNotNull('deleted_at')
          ->findArray();
      }, array_chunk($subscribers_data['email'], 200))
    );
    if(!$existing_trashed_records) return;
    $existing_trashed_records = Helpers::flattenArray($existing_trashed_records);
    foreach(array_chunk($existing_trashed_records, 200) as $subscriber_ids) {
      Subscriber::whereIn('id', $subscriber_ids)
        ->deleteMany();
      SubscriberSegment::whereIn('subscriber_id', $subscriber_ids)
        ->deleteMany();
    }
  }
  
  function extendSubscribersAndFields($subscribers_data, $subscriber_fields) {
    $subscribers_data['created_at'] = $this->filterSubscriberCreatedAtDate();
    $subscriber_fields[] = 'created_at';
    return array(
      $subscribers_data,
      $subscriber_fields
    );
  }
  
  function getSubscriberFields($subscriber_fields) {
    return array_values(
      array_filter(
        array_map(function ($field) {
          if(!is_int($field)) return $field;
        }, $subscriber_fields)
      )
    );
  }
  
  function getCustomSubscriberFields($subscriber_fields) {
    return array_values(
      array_filter(
        array_map(function ($field) {
          if(is_int($field)) return $field;
        }, $subscriber_fields)
      )
    );
  }
  
  function filterSubscriberCreatedAtDate() {
    return array_fill(0, $this->subscribers_count, $this->import_time);
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
    $subscribers_data['status'] = array_map(function ($state) use ($statuses) {
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
    $subscribers_count = count(reset($subscribers_data)) - 1;
    $subscribers = array_map(function ($index) use ($subscribers_data, $subscriber_fields) {
      return array_map(function ($field) use ($index, $subscribers_data) {
        return $subscribers_data[$field][$index];
      }, $subscriber_fields);
    }, range(0, $subscribers_count));
    $import_time = ($action === 'update') ? date('Y-m-d H:i:s') : $this->import_time;
    foreach(array_chunk($subscribers, 100) as $data) {
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
          $import_time
        );
      }
    }
    $result = Helpers::arrayColumn( // return id=>email array of results
      Subscriber::selectMany(
        array(
          'id',
          'email'
        ))
        ->where(($action === 'create') ? 'created_at' : 'updated_at', $import_time)
        ->findArray(),
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
      function ($column) use ($db_subscribers, $subscribers_data) {
        $count = range(0, count($subscribers_data[$column]) - 1);
        return array_map(
          function ($index, $value)
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
      }, $subscriber_custom_fields)[0];
    foreach(array_chunk($subscribers, 200) as $data) {
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