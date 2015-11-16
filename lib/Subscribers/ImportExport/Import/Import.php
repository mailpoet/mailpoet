<?php
namespace MailPoet\Subscribers\ImportExport\Import;

use MailPoet\Subscribers\ImportExport\BootStrapMenu;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Util\Helpers;

class Import {
  public function __construct($data) {
    $this->subscribersData = $data['subscribers'];
    $this->segments = $data['segments'];
    $this->updateSubscribers = $data['updateSubscribers'];
    $this->subscriberFields = $this->getSubscriberFields(
      array_keys($this->subscribersData)
    );
    $this->subscriberCustomFields = $this->getCustomSubscriberFields(
      array_keys($this->subscribersData)
    );
    $this->subscribersCount = count(reset($this->subscribersData));
    $this->currentTime = date('Y-m-d H:i:s');
    $this->profilerStart = microtime(true);
  }

  function process() {
    $subscriberFields = $this->subscriberFields;
    $subscriberCustomFields = $this->subscriberCustomFields;
    $subscribersData = $this->subscribersData;
    $subscribersData = $this->filterSubscriberStatus($subscribersData);
    list($subscribersData, $subscriberFields) = $this->extendSubscribersAndFields(
      $subscribersData, $subscriberFields
    );
    list($existingSubscribers, $newSubscribers) =
      $this->filterExistingAndNewSubscribers($subscribersData);
    $createdSubscribers = $updatedSubscribers = array();
    try {
      if($newSubscribers) {
        $createdSubscribers =
          $this->createOrUpdateSubscribers(
            'create',
            $newSubscribers,
            $subscriberFields,
            $subscriberCustomFields
          );
      }
      if($existingSubscribers && $this->updateSubscribers) {
        $updatedSubscribers =
          $this->createOrUpdateSubscribers(
            'update',
           $existingSubscribers,
            $subscriberFields,
            $subscriberCustomFields
          );
        if($createdSubscribers) {
          // subtract added from updated subscribers when DB operation takes <1s
          $updatedSubscribers = array_diff_key(
            $updatedSubscribers,
            $createdSubscribers,
            $subscriberCustomFields
          );
        }
      }
    } catch (\PDOException $e) {
      return array(
        'result' => false,
        'error' => $e->getMessage()
      );
    }
    $segments = new BootStrapMenu('import');
    return array(
      'result' => true,
      'data' => array(
        'created' => count($createdSubscribers),
        'updated' => count($updatedSubscribers),
        'segments' => $segments->getSegments()
      ),
      'profiler' => $this->timeExecution()
    );
  }

  function filterExistingAndNewSubscribers($subscribersData) {
    $existingRecords = array_filter(
      array_map(function ($subscriberEmails) {
        return Subscriber::selectMany(array('email'))
          ->whereIn('email', $subscriberEmails)
          ->findArray();
      }, array_chunk($subscribersData['email'], 200))
    );
    if(!$existingRecords) {
      return array(
        false,
        $subscribersData
      );
    }
    $existingRecords = Helpers::flattenArray($existingRecords);
    $newRecords = array_keys(
      array_diff(
        $subscribersData['email'],
        $existingRecords
      )
    );
    if(!$newRecords) {
      return array(
        $subscribersData,
        false
      );
    }
    $newSubscribers =
      array_filter(
        array_map(function ($subscriber) use ($newRecords) {
          return array_map(function ($index) use ($subscriber) {
            return $subscriber[$index];
          }, $newRecords);
        }, $subscribersData)
      );

    $existingSubscribers =
      array_map(function ($subscriber) use ($newRecords) {
        return array_values( // reindex array
          array_filter( // remove NULL entries
            array_map(function ($index, $data) use ($newRecords) {
              if(!in_array($index, $newRecords)) return $data;
            }, array_keys($subscriber), $subscriber)
          )
        );
      }, $subscribersData);
    return array(
      $existingSubscribers,
      $newSubscribers
    );
  }

  function extendSubscribersAndFields($subscribersData, $subscriberFields) {
    $subscribersData['created_at'] = $this->filterSubscriberCreatedAtDate();
    $subscriberFields[] = 'created_at';
    return array(
      $subscribersData,
      $subscriberFields
    );
  }

  function getSubscriberFields($subscriberFields) {
    return array_values(
      array_filter(
        array_map(function ($field) {
          if(!is_int($field)) return $field;
        }, $subscriberFields)
      )
    );
  }

  function getCustomSubscriberFields($subscriberFields) {
    return array_values(
      array_filter(
        array_map(function ($field) {
          if(is_int($field)) return $field;
        }, $subscriberFields)
      )
    );
  }

  function filterSubscriberCreatedAtDate() {
    return array_fill(0, $this->subscribersCount, $this->currentTime);
  }

  function filterSubscriberStatus($subscribersData) {
    if(!in_array('status', $this->subscriberFields)) return $subscribersData;
    $statuses = array(
      'subscribed' => array(
        'subscribed',
        'confirmed',
        1,
        '1',
        'true'
      ),
      'unsubscribed' => array(
        'unsubscribed',
        -1,
        '-1',
        'false'
      )
    );
    $subscribersData['status'] = array_map(function ($state) use ($statuses) {
      if(in_array(strtolower($state), $statuses['subscribed'])) {
        return 'confirmed';
      }
      if(in_array(strtolower($state), $statuses['unsubscribed'])) {
        return 'unsubscribed';
      }
      return 'confirmed'; // make "subscribed" a default status
    }, $subscribersData['status']);
    return $subscribersData;
  }

  function createOrUpdateSubscribers(
    $action,
    $subscribersData,
    $subscriberFields,
    $subscriberCustomFields
  ) {
    $subscribersCount = count(reset($subscribersData)) - 1;
    $subscribers = array_map(function ($index) use ($subscribersData, $subscriberFields) {
      return array_map(function ($field) use ($index, $subscribersData) {
        return $subscribersData[$field][$index];
      }, $subscriberFields);
    }, range(0, $subscribersCount));
    $currentTime = ($action === 'update') ? date('Y-m-d H:i:s') : $this->currentTime;
    foreach (array_chunk($subscribers, 200) as $data) {
      if($action == 'create') {
        Subscriber::createMultiple(
          $subscriberFields,
          $data
        );
      }
      if($action == 'update') {
        Subscriber::updateMultiple(
          $subscriberFields,
          $data,
          $currentTime
        );
      }
    }
    $result = Helpers::arrayColumn( // return id=>email array of results
      Subscriber::selectMany(
        array(
          'id',
          'email'
        ))
        ->where(($action === 'create') ? 'created_at' : 'updated_at', $currentTime)
        ->findArray(),
      'email', 'id'
    );
    if($subscriberCustomFields) {
      $this->createOrUpdateCustomFields(
        ($action === 'create') ? 'create' : 'update',
        $result,
        $subscribersData,
        $subscriberCustomFields
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
    $dbSubscribers,
    $subscribersData,
    $subscriberCustomFields
  ) {
    $subscribers = array_map(
      function ($column) use ($dbSubscribers, $subscribersData) {
        $count = range(0, count($subscribersData[$column]) - 1);
        return array_map(
          function ($index, $value)
          use ($dbSubscribers, $subscribersData, $column) {
            $subscriberId = array_search(
              $subscribersData['email'][$index],
              $dbSubscribers
            );
            return array(
              $column,
              $subscriberId,
              $value
            );
          }, $count, $subscribersData[$column]);
      }, $subscriberCustomFields)[0];
    foreach (array_chunk($subscribers, 200) as $data) {
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
    foreach (array_chunk($subscribers, 200) as $data) {
      SubscriberSegment::createMultiple($segments, $data);
    }
  }

  function timeExecution() {
    $profilerEnd = microtime(true);
    return ($profilerEnd - $this->profilerStart) / 60;
  }
}