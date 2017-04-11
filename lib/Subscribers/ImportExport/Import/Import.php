<?php
namespace MailPoet\Subscribers\ImportExport\Import;

use MailPoet\Form\Block\Date;
use MailPoet\Models\CustomField;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\ImportExportFactory;
use MailPoet\Util\Helpers;

class Import {
  public $subscribers_data;
  public $segments;
  public $update_subscribers;
  public $subscribers_fields;
  public $subscribers_custom_fields;
  public $subscribers_count;
  public $created_at;
  public $updated_at;
  public $required_subscribers_fields;
  const DB_QUERY_CHUNK_SIZE = 100;

  public function __construct($data) {
    $this->validateImportData($data);
    $this->subscribers_data = $this->transformSubscribersData(
      $data['subscribers'],
      $data['columns']
    );
    $this->segments = $data['segments'];
    $this->update_subscribers = $data['updateSubscribers'];
    $this->subscribers_fields = $this->getSubscribersFields(
      array_keys($data['columns'])
    );
    $this->subscribers_custom_fields = $this->getCustomSubscribersFields(
      array_keys($data['columns'])
    );
    $this->subscribers_fields_validation_rules = $this->getSubscriberDataValidationRules(
      $data['columns']
    );
    $this->subscribers_count = count(reset($this->subscribers_data));
    $this->created_at = date('Y-m-d H:i:s', (int)$data['timestamp']);
    $this->updated_at = date('Y-m-d H:i:s', (int)$data['timestamp'] + 1);
    $this->required_subscribers_fields = array(
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'first_name' => '',
      'last_name' => '',
      'created_at' => $this->created_at
    );
  }

  function validateImportData($data) {
    $required_data_fields = array(
      'subscribers',
      'columns',
      'segments',
      'timestamp',
      'updateSubscribers'
    );
    // 1. data should contain all required fields
    // 2. column names should only contain alphanumeric & underscore characters
    if(count(array_intersect_key(array_flip($required_data_fields), $data)) !== count($required_data_fields) ||
       preg_grep('/[^a-zA-Z0-9_]/', array_keys($data['columns']))
    ) {
      throw new \Exception(__('Missing or invalid import data.', 'mailpoet'));
    }
  }

  function getSubscriberDataValidationRules($subscribers_fields) {
    $validation_rules = array();
    foreach($subscribers_fields as $column => $field) {
      $validation_rules[$column] = (!empty($field['validation_rule'])) ?
        $field['validation_rule'] :
        false;
    }
    return $validation_rules;
  }

  function process() {
    // validate data based on field validation rules
    $subscribers_data = $this->validateSubscribersData(
      $this->subscribers_data,
      $this->subscribers_fields_validation_rules
    );

    // permanently trash deleted subscribers
    $this->deleteExistingTrashedSubscribers($subscribers_data);

    // split subscribers into "existing" and "new" and free up memory
    $existing_subscribers = $new_subscribers = array(
      'data' => array(),
      'fields' => $this->subscribers_fields
    );
    list($existing_subscribers['data'], $new_subscribers['data'], $wp_users) =
      $this->splitSubscribersData($subscribers_data, $this->subscribers_fields);
    $subscribers_data = null;

    // create or update subscribers
    $created_subscribers = $updated_subscribers = array();
    try {
      if($new_subscribers['data']) {
        // add, if required, missing required fields to new subscribers
        $new_subscribers = $this->addMissingRequiredFields($new_subscribers);
        // filter contents of the "status" field
        $new_subscribers = $this->filterSubscribersStatus($new_subscribers);
        $created_subscribers =
          $this->createOrUpdateSubscribers(
            'create',
            $new_subscribers,
            $this->subscribers_custom_fields
          );
      }
      if($existing_subscribers['data'] && $this->update_subscribers) {
        // filter contents of the "status" field
        $existing_subscribers = $this->filterSubscribersStatus($existing_subscribers);
        $updated_subscribers =
          $this->createOrUpdateSubscribers(
            'update',
            $existing_subscribers,
            $this->subscribers_custom_fields
          );
        if($wp_users) {
          $this->synchronizeWPUsers($wp_users);
        }
      }
    } catch(\Exception $e) {
      throw new \Exception(__('Unable to save imported subscribers.', 'mailpoet'));
    }

    // check if any subscribers were added to segments that have welcome notifications configured
    $import_factory = new ImportExportFactory('import');
    $segments = $import_factory->getSegments();
    $welcome_notifications_in_segments =
      ($created_subscribers || $updated_subscribers) ?
        Newsletter::getWelcomeNotificationsForSegments($this->segments) :
        false;

    return array(
      'created' => count($created_subscribers),
      'updated' => count($updated_subscribers),
      'segments' => $segments,
      'added_to_segment_with_welcome_notification' =>
        ($welcome_notifications_in_segments) ? true : false
    );
  }

  function validateSubscribersData($subscribers_data, $validation_rules) {
    $invalid_records = array();
    foreach($subscribers_data as $column => &$data) {
      $validation_rule = $validation_rules[$column];
      // if this is a custom column
      if(in_array($column, $this->subscribers_custom_fields)) {
        $custom_field = CustomField::findOne($column);
        // validate date type
        if($custom_field->type === 'date') {
          $data = array_map(
            function($index, $date) use($validation_rule, &$invalid_records) {
              if (empty($date)) return $date;
              $date = Date::convertDateToDatetime($date, $validation_rule);
              if(!$date) {
                $invalid_records[] = $index;
              }
              return $date;
            }, array_keys($data), $data);
        }
      }
    }
    if($invalid_records) {
      foreach($subscribers_data as $column => &$data) {
        $data = array_diff_key($data, array_flip($invalid_records));
        $data = array_values($data);
      }
    }
    return $subscribers_data;
  }

  function transformSubscribersData($subscribers, $columns) {
    foreach($columns as $column => $data) {
      $transformed_subscribers[$column] = Helpers::arrayColumn($subscribers, $data['index']);
    }
    return $transformed_subscribers;
  }

  function splitSubscribersData($subscribers_data) {
    $temp_existing_subscribers = array();
    foreach(array_chunk($subscribers_data['email'], self::DB_QUERY_CHUNK_SIZE) as $subscribers_emails) {
      $temp_existing_subscribers = array_merge(
        $temp_existing_subscribers,
        Subscriber::select('wp_user_id')
          ->selectExpr('LOWER(email)', 'email')
          ->whereIn('email', $subscribers_emails)
          ->whereNull('deleted_at')
          ->findArray()
      );
    }
    if(!$temp_existing_subscribers) {
      return array(
        $existing_subscribers = false,
        $new_subscribers = $subscribers_data,
        $wp_users = false
      );
    }
    $wp_users = array_filter(Helpers::arrayColumn($temp_existing_subscribers, 'wp_user_id'));
    $temp_new_subscribers = array_keys(
      array_diff(
        $subscribers_data['email'],
        Helpers::arrayColumn($temp_existing_subscribers, 'email')
      )
    );
    if(!$temp_new_subscribers) {
      return array(
        $existing_subscribers = $subscribers_data,
        $new_subscribers = false,
        $wp_users = $wp_users
      );
    }
    $existing_subscribers = $new_subscribers = array();
    foreach($subscribers_data as $field => $values) {
      $existing_subscribers[$field] = array_diff_key($values, array_flip($temp_new_subscribers));
      $new_subscribers[$field] = array_values(array_intersect_key($values, array_flip($temp_new_subscribers)));
    }
    return array(
      $existing_subscribers,
      $new_subscribers,
      $wp_users
    );
  }

  function deleteExistingTrashedSubscribers($subscribers_data) {
    $existing_trashed_records = array_filter(
      array_map(function($subscriber_emails) {
        return Subscriber::selectMany(array('id'))
          ->whereIn('email', $subscriber_emails)
          ->whereNotNull('deleted_at')
          ->findArray();
      }, array_chunk($subscribers_data['email'], self::DB_QUERY_CHUNK_SIZE))
    );
    if(!$existing_trashed_records) return;
    $existing_trashed_records = Helpers::flattenArray($existing_trashed_records);
    foreach(array_chunk($existing_trashed_records, self::DB_QUERY_CHUNK_SIZE) as
            $subscriber_ids) {
      Subscriber::whereIn('id', $subscriber_ids)
        ->deleteMany();
      SubscriberSegment::whereIn('subscriber_id', $subscriber_ids)
        ->deleteMany();
    }
  }

  function addMissingRequiredFields($subscribers) {
    $subscribers_count = count($subscribers['data'][key($subscribers['data'])]);
    foreach(array_keys($this->required_subscribers_fields) as $required_field) {
      if(in_array($required_field, $subscribers['fields'])) continue;
      $subscribers['data'][$required_field] = array_fill(
        0,
        $subscribers_count,
        $this->required_subscribers_fields[$required_field]
      );
      $subscribers['fields'][] = $required_field;
    }
    return $subscribers;
  }

  function getSubscribersFields($subscribers_fields) {
    return array_values(
      array_filter(
        array_map(function($field) {
          if(!is_int($field)) return $field;
        }, $subscribers_fields)
      )
    );
  }

  function getCustomSubscribersFields($subscribers_fields) {
    return array_values(
      array_filter(
        array_map(function($field) {
          if(is_int($field)) return $field;
        }, $subscribers_fields)
      )
    );
  }

  function filterSubscribersStatus($subscribers_data) {
    if(!in_array('status', $subscribers_data['fields'])) return $subscribers_data;
    $statuses = array(
      Subscriber::STATUS_SUBSCRIBED => array(
        'subscribed',
        'confirmed',
        1,
        '1',
        'true'
      ),
      Subscriber::STATUS_UNCONFIRMED => array(
        'unconfirmed',
        0,
        "0"
      ),
      Subscriber::STATUS_UNSUBSCRIBED => array(
        'unsubscribed',
        -1,
        '-1',
        'false'
      ),
      Subscriber::STATUS_BOUNCED => array(
        'bounced'
      )
    );
    $subscribers_data['data']['status'] = array_map(function($state) use ($statuses) {
      if(in_array(strtolower($state), $statuses[Subscriber::STATUS_SUBSCRIBED], true)) {
        return Subscriber::STATUS_SUBSCRIBED;
      }
      if(in_array(strtolower($state), $statuses[Subscriber::STATUS_UNSUBSCRIBED], true)) {
        return Subscriber::STATUS_UNSUBSCRIBED;
      }
      if(in_array(strtolower($state), $statuses[Subscriber::STATUS_UNCONFIRMED], true)) {
        return Subscriber::STATUS_UNCONFIRMED;
      }
      if(in_array(strtolower($state), $statuses[Subscriber::STATUS_BOUNCED], true)) {
        return Subscriber::STATUS_BOUNCED;
      }
      return Subscriber::STATUS_SUBSCRIBED;
    }, $subscribers_data['data']['status']);
    return $subscribers_data;
  }

  function createOrUpdateSubscribers(
    $action,
    $subscribers_data,
    $subscribers_custom_fields = false
  ) {
    $subscribers_count = count($subscribers_data['data'][key($subscribers_data['data'])]);
    $subscribers = array_map(function($index) use ($subscribers_data) {
      return array_map(function($field) use ($index, $subscribers_data) {
        return $subscribers_data['data'][$field][$index];
      }, $subscribers_data['fields']);
    }, range(0, $subscribers_count - 1));
    foreach(array_chunk($subscribers, self::DB_QUERY_CHUNK_SIZE) as $data) {
      if($action == 'create') {
        Subscriber::createMultiple(
          $subscribers_data['fields'],
          $data
        );
      }
      if($action == 'update') {
        Subscriber::updateMultiple(
          $subscribers_data['fields'],
          $data,
          $this->updated_at
        );
      }
    }
    $query = Subscriber::selectMany(array(
      'id',
      'email'
    ));
    $query = ($action === 'update') ?
      $query->where('updated_at', $this->updated_at) :
      $query->where('created_at', $this->created_at);
    $result = Helpers::arrayColumn(
      $query->findArray(),
      'id'
    );
    if($subscribers_custom_fields) {
      $this->createOrUpdateCustomFields(
        ($action === 'create') ? 'create' : 'update',
        $result,
        $subscribers_data,
        $subscribers_custom_fields
      );
    }
    $this->addSubscribersToSegments(
      $result,
      $this->segments
    );
    return $result;
  }

  function createOrUpdateCustomFields(
    $action,
    $db_subscribers_ids,
    $subscribers_data,
    $subscribers_custom_fields_ids
  ) {
    // check if custom fields exist in the database
    $subscribers_custom_fields_ids = Helpers::flattenArray(
      CustomField::whereIn('id', $subscribers_custom_fields_ids)
        ->select('id')
        ->findArray()
    );
    if(!$subscribers_custom_fields_ids) return;
    $subscriber_custom_fields_data = array();
    foreach($subscribers_data['data'] as $field_id => $subscriber_data) {
      // exclude non-custom fields
      if(!is_int($field_id)) continue;
      $subscriber_index = 0;
      foreach($subscriber_data as $value) {
        // assemble an array: custom_field_id, subscriber_id, value
        $subscriber_custom_fields_data[] = array(
          (int)$field_id,
          (int)$db_subscribers_ids[$subscriber_index],
          $value
        );
        $subscriber_index++;
      }
    }
    foreach(array_chunk($subscriber_custom_fields_data, self::DB_QUERY_CHUNK_SIZE) as $data) {
      SubscriberCustomField::createMultiple(
        $data
      );
      if($action === 'update') {
        SubscriberCustomField::updateMultiple(
          $data
        );
      }
    }
  }

  function synchronizeWPUsers($wp_users) {
    return array_walk($wp_users, '\MailPoet\Segments\WP::synchronizeUser');
  }

  function addSubscribersToSegments($subscriber_ids, $segment_ids) {
    foreach(array_chunk($subscriber_ids, self::DB_QUERY_CHUNK_SIZE) as $subscriber_ids_chunk) {
      SubscriberSegment::subscribeManyToSegments(
        $subscriber_ids_chunk, $segment_ids
      );
    }
  }
}