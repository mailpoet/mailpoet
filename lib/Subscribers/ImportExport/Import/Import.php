<?php
namespace MailPoet\Subscribers\ImportExport\Import;

use MailPoet\Form\Block\Date;
use MailPoet\Models\CustomField;
use MailPoet\Models\ModelValidator;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\ImportExportFactory;
use MailPoet\Subscribers\Source;
use MailPoet\Util\Helpers;
use function MailPoet\Util\array_column;

class Import {
  public $subscribers_data;
  public $segments_ids;
  public $update_subscribers;
  public $subscribers_fields;
  public $subscribers_custom_fields;
  public $subscribers_fields_validation_rules;
  public $subscribers_count;
  public $created_at;
  public $updated_at;
  public $required_subscribers_fields;
  private $default_subscribers_data_validation_rules = [
    'email' => 'email',
  ];
  const DB_QUERY_CHUNK_SIZE = 100;

  public function __construct($data) {
    $this->validateImportData($data);
    $this->subscribers_data = $this->transformSubscribersData(
      $data['subscribers'],
      $data['columns']
    );
    $this->segments_ids = $data['segments'];
    $this->update_subscribers = $data['updateSubscribers'];
    $this->subscribers_fields = $this->getSubscribersFields(
      array_keys($data['columns'])
    );
    $this->subscribers_custom_fields = $this->getCustomSubscribersFields(
      array_keys($data['columns'])
    );
    $this->subscribers_fields_validation_rules = $this->getSubscriberDataValidationRules($data['columns']);
    $this->subscribers_count = count(reset($this->subscribers_data));
    $this->created_at = date('Y-m-d H:i:s', (int)$data['timestamp']);
    $this->updated_at = date('Y-m-d H:i:s', (int)$data['timestamp'] + 1);
    $this->required_subscribers_fields = [
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'first_name' => '',
      'last_name' => '',
      'created_at' => $this->created_at,
    ];
  }

  function validateImportData($data) {
    $required_data_fields = [
      'subscribers',
      'columns',
      'segments',
      'timestamp',
      'updateSubscribers',
    ];
    // 1. data should contain all required fields
    // 2. column names should only contain alphanumeric & underscore characters
    if (count(array_intersect_key(array_flip($required_data_fields), $data)) !== count($required_data_fields) ||
       preg_grep('/[^a-zA-Z0-9_]/', array_keys($data['columns']))
    ) {
      throw new \Exception(__('Missing or invalid import data.', 'mailpoet'));
    }
  }

  function getSubscriberDataValidationRules($subscribers_fields) {
    $validation_rules = [];
    foreach ($subscribers_fields as $column => $field) {
      $validation_rules[$column] = (!empty($field['validation_rule'])) ?
        $field['validation_rule'] :
        false;
    }
    return array_replace($validation_rules, $this->default_subscribers_data_validation_rules);
  }

  function process() {
    // validate data based on field validation rules
    $subscribers_data = $this->validateSubscribersData(
      $this->subscribers_data,
      $this->subscribers_fields_validation_rules
    );
    if (!$subscribers_data) {
      throw new \Exception(__('No valid subscribers were found.', 'mailpoet'));
    }
    // permanently trash deleted subscribers
    $this->deleteExistingTrashedSubscribers($subscribers_data);

    // split subscribers into "existing" and "new" and free up memory
    $existing_subscribers = $new_subscribers = [
      'data' => [],
      'fields' => $this->subscribers_fields,
    ];
    list($existing_subscribers['data'], $new_subscribers['data'], $wp_users) =
      $this->splitSubscribersData($subscribers_data);
    $subscribers_data = null;

    // create or update subscribers
    $created_subscribers = $updated_subscribers = [];
    try {
      if ($new_subscribers['data']) {
        // add, if required, missing required fields to new subscribers
        $new_subscribers = $this->addMissingRequiredFields($new_subscribers);
        $new_subscribers = $this->setSubscriptionStatusToSubscribed($new_subscribers);
        $new_subscribers = $this->setSource($new_subscribers);
        $created_subscribers =
          $this->createOrUpdateSubscribers(
            'create',
            $new_subscribers,
            $this->subscribers_custom_fields
          );
      }
      if ($existing_subscribers['data'] && $this->update_subscribers) {
        $updated_subscribers =
          $this->createOrUpdateSubscribers(
            'update',
            $existing_subscribers,
            $this->subscribers_custom_fields
          );
        if ($wp_users) {
          $this->synchronizeWPUsers($wp_users);
        }
      }
    } catch (\Exception $e) {
      throw new \Exception(__('Unable to save imported subscribers.', 'mailpoet'));
    }

    // check if any subscribers were added to segments that have welcome notifications configured
    $import_factory = new ImportExportFactory('import');
    $segments = $import_factory->getSegments();
    $welcome_notifications_in_segments =
      ($created_subscribers || $updated_subscribers) ?
        Newsletter::getWelcomeNotificationsForSegments($this->segments_ids) :
        false;

    return [
      'created' => count($created_subscribers),
      'updated' => count($updated_subscribers),
      'segments' => $segments,
      'added_to_segment_with_welcome_notification' =>
        ($welcome_notifications_in_segments) ? true : false,
    ];
  }

  function validateSubscribersData($subscribers_data, $validation_rules) {
    $invalid_records = [];
    $validator = new ModelValidator();
    foreach ($subscribers_data as $column => &$data) {
      $validation_rule = $validation_rules[$column];
      if ($validation_rule === 'email') {
        $data = array_map(
          function($index, $email) use(&$invalid_records, $validator) {
            if (!$validator->validateNonRoleEmail($email)) {
              $invalid_records[] = $index;
            }
            return strtolower($email);
          }, array_keys($data), $data
        );
      }
      // if this is a custom column
      if (in_array($column, $this->subscribers_custom_fields)) {
        $custom_field = CustomField::findOne($column);
        // validate date type
        if ($custom_field->type === 'date') {
          $data = array_map(
            function($index, $date) use($validation_rule, &$invalid_records) {
              if (empty($date)) return $date;
              $date = Date::convertDateToDatetime($date, $validation_rule);
              if (!$date) {
                $invalid_records[] = $index;
              }
              return $date;
            }, array_keys($data), $data
          );
        }
      }
    }
    if ($invalid_records) {
      foreach ($subscribers_data as $column => &$data) {
        $data = array_diff_key($data, array_flip($invalid_records));
        $data = array_values($data);
      }
    }
    if (empty($subscribers_data['email'])) return false;
    return $subscribers_data;
  }

  function transformSubscribersData($subscribers, $columns) {
    $transformed_subscribers = [];
    foreach ($columns as $column => $data) {
      $transformed_subscribers[$column] = array_column($subscribers, $data['index']);
    }
    return $transformed_subscribers;
  }

  function splitSubscribersData($subscribers_data) {
    // $subscribers_data is an two-dimensional associative array
    // of all subscribers being imported: [field => [value1, value2], field => [value1, value2], ...]
    $temp_existing_subscribers = [];
    foreach (array_chunk($subscribers_data['email'], self::DB_QUERY_CHUNK_SIZE) as $subscribers_emails) {
      // create a two-dimensional indexed array of all existing subscribers
      // with just wp_user_id and email fields: [[wp_user_id, email], [wp_user_id, email], ...]
      $temp_existing_subscribers = array_merge(
        $temp_existing_subscribers,
        Subscriber::select('wp_user_id')
          ->selectExpr('LOWER(email)', 'email')
          ->whereIn('email', $subscribers_emails)
          ->whereNull('deleted_at')
          ->findArray()
      );
    }
    if (!$temp_existing_subscribers) {
      return [
        false, // existing subscribers
        $subscribers_data, // new subscribers
        false, // WP users
      ];
    }
    // extract WP users ids into a simple indexed array: [wp_user_id_1, wp_user_id_2, ...]
    $wp_users = array_filter(array_column($temp_existing_subscribers, 'wp_user_id'));
    // create a new two-dimensional associative array with existing subscribers ($existing_subscribers)
    // and reduce $subscribers_data to only new subscribers by removing existing subscribers
    $existing_subscribers = [];
    $subscribers_emails = array_flip($subscribers_data['email']);
    foreach ($temp_existing_subscribers as $temp_existing_subscriber) {
      $existing_subscriber_key = $subscribers_emails[$temp_existing_subscriber['email']];
      foreach ($subscribers_data as $field => &$value) {
        $existing_subscribers[$field][] = $value[$existing_subscriber_key];
        unset($value[$existing_subscriber_key]);
      }
    }
    $new_subscribers = $subscribers_data;
    // reindex array after unsetting elements
    $new_subscribers = array_map('array_values', $new_subscribers);
    // remove empty values
    $new_subscribers = array_filter($new_subscribers);
    return [
      $existing_subscribers,
      $new_subscribers,
      $wp_users,
    ];
  }

  function deleteExistingTrashedSubscribers($subscribers_data) {
    $existing_trashed_records = array_filter(
      array_map(function($subscriber_emails) {
        return Subscriber::selectMany(['id'])
          ->whereIn('email', $subscriber_emails)
          ->whereNotNull('deleted_at')
          ->findArray();
      }, array_chunk($subscribers_data['email'], self::DB_QUERY_CHUNK_SIZE))
    );
    if (!$existing_trashed_records) return;
    $existing_trashed_records = Helpers::flattenArray($existing_trashed_records);
    foreach (array_chunk($existing_trashed_records, self::DB_QUERY_CHUNK_SIZE) as $subscriber_ids) {
      Subscriber::whereIn('id', $subscriber_ids)
        ->deleteMany();
      SubscriberSegment::whereIn('subscriber_id', $subscriber_ids)
        ->deleteMany();
    }
  }

  function addMissingRequiredFields($subscribers) {
    $subscribers_count = count($subscribers['data'][key($subscribers['data'])]);
    foreach (array_keys($this->required_subscribers_fields) as $required_field) {
      if (in_array($required_field, $subscribers['fields'])) continue;
      $subscribers['data'][$required_field] = array_fill(
        0,
        $subscribers_count,
        $this->required_subscribers_fields[$required_field]
      );
      $subscribers['fields'][] = $required_field;
    }
    return $subscribers;
  }

  private function setSubscriptionStatusToSubscribed($subscribers_data) {
    if (!in_array('status', $subscribers_data['fields'])) return $subscribers_data;
    $subscribers_data['data']['status'] = array_map(function() {
      return Subscriber::STATUS_SUBSCRIBED;
    }, $subscribers_data['data']['status']);

    if (!in_array('last_subscribed_at', $subscribers_data['fields'])) {
      $subscribers_data['fields'][] = 'last_subscribed_at';
    }
    $subscribers_data['data']['last_subscribed_at'] = array_map(function() {
      return $this->created_at;
    }, $subscribers_data['data']['status']);
    return $subscribers_data;
  }

  function setSource($subscribers_data) {
    $subscribers_count = count($subscribers_data['data'][key($subscribers_data['data'])]);
    $subscribers_data['fields'][] = 'source';
    $subscribers_data['data']['source'] = array_fill(
      0,
      $subscribers_count,
      Source::IMPORTED
    );
    return $subscribers_data;
  }

  function getSubscribersFields($subscribers_fields) {
    return array_values(
      array_filter(
        array_map(function($field) {
          if (!is_int($field)) return $field;
        }, $subscribers_fields)
      )
    );
  }

  function getCustomSubscribersFields($subscribers_fields) {
    return array_values(
      array_filter(
        array_map(function($field) {
          if (is_int($field)) return $field;
        }, $subscribers_fields)
      )
    );
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
    foreach (array_chunk($subscribers, self::DB_QUERY_CHUNK_SIZE) as $data) {
      if ($action == 'create') {
        Subscriber::createMultiple(
          $subscribers_data['fields'],
          $data
        );
      }
      if ($action == 'update') {
        Subscriber::updateMultiple(
          $subscribers_data['fields'],
          $data,
          $this->updated_at
        );
      }
    }
    $created_or_updated_subscribers = [];
    foreach (array_chunk($subscribers_data['data']['email'], self::DB_QUERY_CHUNK_SIZE) as $data) {
      $created_or_updated_subscribers = array_merge(
        $created_or_updated_subscribers,
        Subscriber::selectMany(['id', 'email'])->whereIn('email', $data)->findArray()
      );
    }
    if (empty($created_or_updated_subscribers)) return null;
    $created_or_updated_subscribers_ids = array_column($created_or_updated_subscribers, 'id');
    if ($subscribers_custom_fields) {
      $this->createOrUpdateCustomFields(
        $action,
        $created_or_updated_subscribers,
        $subscribers_data,
        $subscribers_custom_fields
      );
    }
    $this->addSubscribersToSegments(
      $created_or_updated_subscribers_ids,
      $this->segments_ids
    );
    return $created_or_updated_subscribers;
  }

  function createOrUpdateCustomFields(
    $action,
    $created_or_updated_subscribers,
    $subscribers_data,
    $subscribers_custom_fields_ids
  ) {
    // check if custom fields exist in the database
    $subscribers_custom_fields_ids = Helpers::flattenArray(
      CustomField::whereIn('id', $subscribers_custom_fields_ids)
        ->select('id')
        ->findArray()
    );
    if (!$subscribers_custom_fields_ids) return;
    // assemble a two-dimensional array: [[custom_field_id, subscriber_id, value], [custom_field_id, subscriber_id, value], ...]
    $subscribers_custom_fields_data = [];
    $subscribers_emails = array_flip($subscribers_data['data']['email']);
    foreach ($created_or_updated_subscribers as $created_or_updated_subscriber) {
      $subscriber_index = $subscribers_emails[$created_or_updated_subscriber['email']];
      foreach ($subscribers_data['data'] as $field => $values) {
        // exclude non-custom fields
        if (!is_int($field)) continue;
        $subscribers_custom_fields_data[] = [
          (int)$field,
          $created_or_updated_subscriber['id'],
          $values[$subscriber_index],
        ];
      }
    }
    foreach (array_chunk($subscribers_custom_fields_data, self::DB_QUERY_CHUNK_SIZE) as $subscribers_custom_fields_data_chunk) {
      SubscriberCustomField::createMultiple(
        $subscribers_custom_fields_data_chunk
      );
      if ($action === 'update') {
        SubscriberCustomField::updateMultiple(
          $subscribers_custom_fields_data_chunk
        );
      }
    }
  }

  function synchronizeWPUsers($wp_users) {
    return array_walk($wp_users, '\MailPoet\Segments\WP::synchronizeUser');
  }

  function addSubscribersToSegments($subscribers_ids, $segments_ids) {
    foreach (array_chunk($subscribers_ids, self::DB_QUERY_CHUNK_SIZE) as $subscriber_ids_chunk) {
      SubscriberSegment::subscribeManyToSegments(
        $subscriber_ids_chunk, $segments_ids
      );
    }
  }
}
