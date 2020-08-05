<?php

namespace MailPoet\Subscribers\ImportExport\Import;

use MailPoet\Models\CustomField;
use MailPoet\Models\ModelValidator;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\ImportExportFactory;
use MailPoet\Subscribers\Source;
use MailPoet\Util\DateConverter;
use MailPoet\Util\Helpers;
use MailPoet\Util\Security;

use function MailPoetVendor\array_column;

class Import {
  public $subscribersData;
  public $segmentsIds;
  public $newSubscribersStatus;
  public $existingSubscribersStatus;
  public $updateSubscribers;
  public $subscribersFields;
  public $subscribersCustomFields;
  public $subscribersCount;
  public $createdAt;
  public $updatedAt;
  public $requiredSubscribersFields;
  const DB_QUERY_CHUNK_SIZE = 100;
  const STATUS_DONT_UPDATE = 'dont_update';

  public function __construct($data) {
    $this->validateImportData($data);
    $this->subscribersData = $this->transformSubscribersData(
      $data['subscribers'],
      $data['columns']
    );
    $this->segmentsIds = $data['segments'];
    $this->newSubscribersStatus = $data['newSubscribersStatus'];
    $this->existingSubscribersStatus = $data['existingSubscribersStatus'];
    $this->updateSubscribers = $data['updateSubscribers'];
    $this->subscribersFields = $this->getSubscribersFields(
      array_keys($data['columns'])
    );
    $this->subscribersCustomFields = $this->getCustomSubscribersFields(
      array_keys($data['columns'])
    );
    $this->subscribersCount = count(reset($this->subscribersData));
    $this->createdAt = date('Y-m-d H:i:s', (int)$data['timestamp']);
    $this->updatedAt = date('Y-m-d H:i:s', (int)$data['timestamp'] + 1);
    $this->requiredSubscribersFields = [
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'first_name' => '',
      'last_name' => '',
      'created_at' => $this->createdAt,
    ];
  }

  public function validateImportData($data) {
    $requiredDataFields = [
      'subscribers',
      'columns',
      'segments',
      'timestamp',
      'newSubscribersStatus',
      'existingSubscribersStatus',
      'updateSubscribers',
    ];
    // 1. data should contain all required fields
    // 2. column names should only contain alphanumeric & underscore characters
    if (count(array_intersect_key(array_flip($requiredDataFields), $data)) !== count($requiredDataFields) ||
       preg_grep('/[^a-zA-Z0-9_]/', array_keys($data['columns']))
    ) {
      throw new \Exception(__('Missing or invalid import data.', 'mailpoet'));
    }
  }

  public function process() {
    // validate data based on field validation rules
    $subscribersData = $this->validateSubscribersData($this->subscribersData);
    if (!$subscribersData) {
      throw new \Exception(__('No valid subscribers were found.', 'mailpoet'));
    }
    // permanently trash deleted subscribers
    $this->deleteExistingTrashedSubscribers($subscribersData);

    // split subscribers into "existing" and "new" and free up memory
    $existingSubscribers = $newSubscribers = [
      'data' => [],
      'fields' => $this->subscribersFields,
    ];
    list($existingSubscribers['data'], $newSubscribers['data'], $wpUsers) =
      $this->splitSubscribersData($subscribersData);
    $subscribersData = null;

    // create or update subscribers
    $createdSubscribers = $updatedSubscribers = [];
    try {
      if ($newSubscribers['data']) {
        // add, if required, missing required fields to new subscribers
        $newSubscribers = $this->addMissingRequiredFields($newSubscribers);
        $newSubscribers = $this->setSubscriptionStatusToDefault($newSubscribers, $this->newSubscribersStatus);
        $newSubscribers = $this->setSource($newSubscribers);
        $newSubscribers = $this->setLinkToken($newSubscribers);
        $createdSubscribers =
          $this->createOrUpdateSubscribers(
            'create',
            $newSubscribers,
            $this->subscribersCustomFields
          );
      }
      if ($existingSubscribers['data'] && $this->updateSubscribers) {
        $allowedStatuses = [
          Subscriber::STATUS_SUBSCRIBED,
          Subscriber::STATUS_UNSUBSCRIBED,
          Subscriber::STATUS_INACTIVE,
        ];
        if (in_array($this->existingSubscribersStatus, $allowedStatuses, true)) {
          $existingSubscribers = $this->addField($existingSubscribers, 'status', $this->existingSubscribersStatus);
        }
        $updatedSubscribers =
          $this->createOrUpdateSubscribers(
            'update',
            $existingSubscribers,
            $this->subscribersCustomFields
          );
        if ($wpUsers) {
          $this->synchronizeWPUsers($wpUsers);
        }
      }
    } catch (\Exception $e) {
      throw new \Exception(__('Unable to save imported subscribers.', 'mailpoet'));
    }

    // check if any subscribers were added to segments that have welcome notifications configured
    $importFactory = new ImportExportFactory('import');
    $segments = $importFactory->getSegments();
    $welcomeNotificationsInSegments =
      ($createdSubscribers || $updatedSubscribers) ?
        Newsletter::getWelcomeNotificationsForSegments($this->segmentsIds) :
        false;

    return [
      'created' => count($createdSubscribers),
      'updated' => count($updatedSubscribers),
      'segments' => $segments,
      'added_to_segment_with_welcome_notification' =>
        ($welcomeNotificationsInSegments) ? true : false,
    ];
  }

  public function validateSubscribersData($subscribersData) {
    $invalidRecords = [];
    $validator = new ModelValidator();
    foreach ($subscribersData as $column => &$data) {
      if ($column === 'email') {
        $data = array_map(
          function($index, $email) use(&$invalidRecords, $validator) {
            if (!$validator->validateNonRoleEmail($email)) {
              $invalidRecords[] = $index;
            }
            return strtolower($email);
          }, array_keys($data), $data
        );
      }
      // if this is a custom column
      if (in_array($column, $this->subscribersCustomFields)) {
        $customField = CustomField::findOne($column);
        if (!$customField instanceof CustomField) {
          continue;
        }
        // validate date type
        if ($customField->type === 'date') {
          $validationRule = 'datetime';
          $data = array_map(
            function($index, $date) use($validationRule, &$invalidRecords) {
              if (empty($date)) return $date;
              $date = (new DateConverter())->convertDateToDatetime($date, $validationRule);
              if (!$date) {
                $invalidRecords[] = $index;
              }
              return $date;
            }, array_keys($data), $data
          );
        }
      }
    }
    if ($invalidRecords) {
      foreach ($subscribersData as $column => &$data) {
        $data = array_diff_key($data, array_flip($invalidRecords));
        $data = array_values($data);
      }
    }
    if (empty($subscribersData['email'])) return false;
    return $subscribersData;
  }

  public function transformSubscribersData($subscribers, $columns) {
    $transformedSubscribers = [];
    foreach ($columns as $column => $data) {
      $transformedSubscribers[$column] = array_column($subscribers, $data['index']);
    }
    return $transformedSubscribers;
  }

  public function splitSubscribersData($subscribersData) {
    // $subscribers_data is an two-dimensional associative array
    // of all subscribers being imported: [field => [value1, value2], field => [value1, value2], ...]
    $tempExistingSubscribers = [];
    foreach (array_chunk($subscribersData['email'], self::DB_QUERY_CHUNK_SIZE) as $subscribersEmails) {
      // create a two-dimensional indexed array of all existing subscribers
      // with just wp_user_id and email fields: [[wp_user_id, email], [wp_user_id, email], ...]
      $tempExistingSubscribers = array_merge(
        $tempExistingSubscribers,
        Subscriber::select('wp_user_id')
          ->selectExpr('LOWER(email)', 'email')
          ->whereIn('email', $subscribersEmails)
          ->whereNull('deleted_at')
          ->findArray()
      );
    }
    if (!$tempExistingSubscribers) {
      return [
        false, // existing subscribers
        $subscribersData, // new subscribers
        false, // WP users
      ];
    }
    // extract WP users ids into a simple indexed array: [wp_user_id_1, wp_user_id_2, ...]
    $wpUsers = array_filter(array_column($tempExistingSubscribers, 'wp_user_id'));
    // create a new two-dimensional associative array with existing subscribers ($existing_subscribers)
    // and reduce $subscribers_data to only new subscribers by removing existing subscribers
    $existingSubscribers = [];
    $subscribersEmails = array_flip($subscribersData['email']);
    foreach ($tempExistingSubscribers as $tempExistingSubscriber) {
      $existingSubscriberKey = $subscribersEmails[$tempExistingSubscriber['email']];
      foreach ($subscribersData as $field => &$value) {
        $existingSubscribers[$field][] = $value[$existingSubscriberKey];
        unset($value[$existingSubscriberKey]);
      }
    }
    $newSubscribers = $subscribersData;
    // reindex array after unsetting elements
    $newSubscribers = array_map('array_values', $newSubscribers);
    // remove empty values
    $newSubscribers = array_filter($newSubscribers);
    return [
      $existingSubscribers,
      $newSubscribers,
      $wpUsers,
    ];
  }

  public function deleteExistingTrashedSubscribers($subscribersData) {
    $existingTrashedRecords = array_filter(
      array_map(function($subscriberEmails) {
        return Subscriber::selectMany(['id'])
          ->whereIn('email', $subscriberEmails)
          ->whereNotNull('deleted_at')
          ->findArray();
      }, array_chunk($subscribersData['email'], self::DB_QUERY_CHUNK_SIZE))
    );
    if (!$existingTrashedRecords) return;
    $existingTrashedRecords = Helpers::flattenArray($existingTrashedRecords);
    foreach (array_chunk($existingTrashedRecords, self::DB_QUERY_CHUNK_SIZE) as $subscriberIds) {
      Subscriber::whereIn('id', $subscriberIds)
        ->deleteMany();
      SubscriberSegment::whereIn('subscriber_id', $subscriberIds)
        ->deleteMany();
    }
  }

  public function addMissingRequiredFields($subscribers) {
    foreach (array_keys($this->requiredSubscribersFields) as $requiredField) {
      $subscribers = $this->addField($subscribers, $requiredField, $this->requiredSubscribersFields[$requiredField]);
    }
    return $subscribers;
  }

  private function addField($subscribers, $fieldName, $fieldValue) {
    if (in_array($fieldName, $subscribers['fields'])) return $subscribers;

    $subscribersCount = count($subscribers['data'][key($subscribers['data'])]);
    $subscribers['data'][$fieldName] = array_fill(
      0,
      $subscribersCount,
      $fieldValue

    );
    $subscribers['fields'][] = $fieldName;

    return $subscribers;
  }

  private function setSubscriptionStatusToDefault($subscribersData, $defaultStatus) {
    if (!in_array('status', $subscribersData['fields'])) return $subscribersData;
    $subscribersData['data']['status'] = array_map(function() use ($defaultStatus) {
      return $defaultStatus;
    }, $subscribersData['data']['status']);

    if ($defaultStatus === Subscriber::STATUS_SUBSCRIBED) {
      if (!in_array('last_subscribed_at', $subscribersData['fields'])) {
        $subscribersData['fields'][] = 'last_subscribed_at';
      }
      $subscribersData['data']['last_subscribed_at'] = array_map(function() {
        return $this->createdAt;
      }, $subscribersData['data']['status']);
    }
    return $subscribersData;
  }

  private function setSource($subscribersData) {
    $subscribersCount = count($subscribersData['data'][key($subscribersData['data'])]);
    $subscribersData['fields'][] = 'source';
    $subscribersData['data']['source'] = array_fill(
      0,
      $subscribersCount,
      Source::IMPORTED
    );
    return $subscribersData;
  }

  private function setLinkToken($subscribersData) {
    $subscribersCount = count($subscribersData['data'][key($subscribersData['data'])]);
    $subscribersData['fields'][] = 'link_token';
    $subscribersData['data']['link_token'] = array_map(
      function () {
        return Security::generateRandomString(Subscriber::LINK_TOKEN_LENGTH);
      }, array_fill(0, $subscribersCount, null)
    );
    return $subscribersData;
  }

  public function getSubscribersFields($subscribersFields) {
    return array_values(
      array_filter(
        array_map(function($field) {
          if (!is_int($field)) return $field;
        }, $subscribersFields)
      )
    );
  }

  public function getCustomSubscribersFields($subscribersFields) {
    return array_values(
      array_filter(
        array_map(function($field) {
          if (is_int($field)) return $field;
        }, $subscribersFields)
      )
    );
  }

  public function createOrUpdateSubscribers(
    $action,
    $subscribersData,
    $subscribersCustomFields = false
  ) {
    $subscribersCount = count($subscribersData['data'][key($subscribersData['data'])]);
    $subscribers = array_map(function($index) use ($subscribersData) {
      return array_map(function($field) use ($index, $subscribersData) {
        return $subscribersData['data'][$field][$index];
      }, $subscribersData['fields']);
    }, range(0, $subscribersCount - 1));
    foreach (array_chunk($subscribers, self::DB_QUERY_CHUNK_SIZE) as $data) {
      if ($action == 'create') {
        Subscriber::createMultiple(
          $subscribersData['fields'],
          $data
        );
      }
      if ($action == 'update') {
        Subscriber::updateMultiple(
          $subscribersData['fields'],
          $data,
          $this->updatedAt
        );
      }
    }
    $createdOrUpdatedSubscribers = [];
    foreach (array_chunk($subscribersData['data']['email'], self::DB_QUERY_CHUNK_SIZE) as $data) {
      foreach (Subscriber::selectMany(['id', 'email'])->whereIn('email', $data)->findArray() as $createdOrUpdatedSubscriber) {
        // ensure emails loaded from the DB are lowercased (imported emails are lowercased as well)
        $createdOrUpdatedSubscriber['email'] = mb_strtolower($createdOrUpdatedSubscriber['email']);
        $createdOrUpdatedSubscribers[] = $createdOrUpdatedSubscriber;
      }
    }
    if (empty($createdOrUpdatedSubscribers)) return null;
    $createdOrUpdatedSubscribersIds = array_column($createdOrUpdatedSubscribers, 'id');
    if ($subscribersCustomFields) {
      $this->createOrUpdateCustomFields(
        $action,
        $createdOrUpdatedSubscribers,
        $subscribersData,
        $subscribersCustomFields
      );
    }
    $this->addSubscribersToSegments(
      $createdOrUpdatedSubscribersIds,
      $this->segmentsIds
    );
    return $createdOrUpdatedSubscribers;
  }

  public function createOrUpdateCustomFields(
    $action,
    $createdOrUpdatedSubscribers,
    $subscribersData,
    $subscribersCustomFieldsIds
  ) {
    // check if custom fields exist in the database
    $subscribersCustomFieldsIds = Helpers::flattenArray(
      CustomField::whereIn('id', $subscribersCustomFieldsIds)
        ->select('id')
        ->findArray()
    );
    if (!$subscribersCustomFieldsIds) return;
    // assemble a two-dimensional array: [[custom_field_id, subscriber_id, value], [custom_field_id, subscriber_id, value], ...]
    $subscribersCustomFieldsData = [];
    $subscribersEmails = array_flip($subscribersData['data']['email']);
    foreach ($createdOrUpdatedSubscribers as $createdOrUpdatedSubscriber) {
      $subscriberIndex = $subscribersEmails[$createdOrUpdatedSubscriber['email']];
      foreach ($subscribersData['data'] as $field => $values) {
        // exclude non-custom fields
        if (!is_int($field)) continue;
        $subscribersCustomFieldsData[] = [
          (int)$field,
          $createdOrUpdatedSubscriber['id'],
          $values[$subscriberIndex],
        ];
      }
    }
    foreach (array_chunk($subscribersCustomFieldsData, self::DB_QUERY_CHUNK_SIZE) as $subscribersCustomFieldsDataChunk) {
      SubscriberCustomField::createMultiple(
        $subscribersCustomFieldsDataChunk
      );
      if ($action === 'update') {
        SubscriberCustomField::updateMultiple(
          $subscribersCustomFieldsDataChunk
        );
      }
    }
  }

  public function synchronizeWPUsers($wpUsers) {
    return array_walk($wpUsers, '\MailPoet\Segments\WP::synchronizeUser');
  }

  public function addSubscribersToSegments($subscribersIds, $segmentsIds) {
    foreach (array_chunk($subscribersIds, self::DB_QUERY_CHUNK_SIZE) as $subscriberIdsChunk) {
      SubscriberSegment::subscribeManyToSegments(
        $subscriberIdsChunk, $segmentsIds
      );
    }
  }
}
