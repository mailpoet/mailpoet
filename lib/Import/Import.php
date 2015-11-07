<?php namespace MailPoet\Import;

use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Util\Helpers;

class Import {
  public function __construct($data) {
    $this->subscribersData = $data['subscribers'];
    $this->segments = $data['segments'];
    $this->updateSubscribers = $data['updateSubscribers'];
    $this->subscriberFields = $this->getSubscriberFields();
    $this->subscriberCustomFields = $this->getCustomSubscriberFields();
    $this->subscribersCount = count(reset($this->subscribersData));
    $this->currentTime = date('Y-m-d H:i:s');
    $this->profilerStart = microtime(true);
  }

  function process() {
    $subscriberFields = $this->subscriberFields;
    $subscribersData = $this->subscribersData;
    $subscribersData = $this->filterSubscriberState($subscribersData);
    list($subscribersData, $subscriberFields) = $this->extendSubscribersAndFields(
      $subscribersData, $subscriberFields
    );
    list($existingSubscribers, $newSubscribers) = $this->splitSubscribers(
      $subscribersData
    );
    $addedSubscribers = $updatedSubscribers = array();
    if($newSubscribers) {
      $addedSubscribers = $this->addOrUpdateSubscribers(
        'create',
        $newSubscribers,
        $subscriberFields
      );

    }
    if($existingSubscribers && $this->updateSubscribers) {
      $updatedSubscribers = $this->addOrUpdateSubscribers(
        'update',
        $existingSubscribers,
        $subscriberFields
      );
      if($addedSubscribers) {
        $updatedSubscribers = array_diff_key(
          $updatedSubscribers,
          $addedSubscribers
        );
      }
    }
    return array(
      'result' => true,
      'data' => array(
        'added' => count($addedSubscribers),
        'updated' => count($updatedSubscribers),
      ),
      'profile' => $this->timeExecution()
    );
  }

  function splitSubscribers($subscribersData) {
    $existingRecords = array_filter(
      array_map(function ($subscriberEmails) {
        return Subscriber::selectMany(array('email'))
          ->whereIn('email', $subscriberEmails)
          ->findArray();
      }, array_chunk($subscribersData['s_email'], 200))
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
        $subscribersData['s_email'],
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

  function getSubscriberFields() {
    return array_filter(
      array_map(function ($field) {
        if(!is_int($field)) return $field;
      }, array_keys($this->subscribersData))
    );
  }

  function getCustomSubscriberFields() {
    return array_filter(
      array_map(function ($field) {
        if(is_int($field)) return $field;
      }, array_keys($this->subscribersData))
    );
  }

  function filterSubscriberCreatedAtDate() {
    return array_fill(0, $this->subscribersCount, $this->currentTime);
  }

  function filterSubscriberState($subscribersData) {
    if(!in_array('s_status', $this->subscriberFields)) return;
    $states = array(
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
    $subscribersData['s_status'] = array_map(function ($state) use ($states) {
      if(in_array(strtolower($state), $states['subscribed'])) {
        return 1;
      }
      if(in_array(strtolower($state), $states['unsubscribed'])) {
        return -1;
      }
      return 1; // make "subscribed" a default state
    }, $subscribersData['s_status']);
    return $subscribersData;
  }

  function addOrUpdateSubscribers($action, $subscribersData, $subscriberFields) {
    $subscribersCount = count(reset($subscribersData)) - 1;
    $subscribers = array_map(function ($index) use ($subscribersData, $subscriberFields) {
      return array_map(function ($field) use ($index, $subscribersData) {
        return $subscribersData[$field][$index];
      }, $subscriberFields);
    }, range(0, $subscribersCount));
    $subscriberFields = str_replace('s_', '', $subscriberFields);
    $currentTime = ($action === 'update') ? date('Y-m-d H:i:s') : $this->currentTime;
    foreach (array_chunk($subscribers, 200) as $data) {
      try {
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
      } catch (\PDOException $e) {
        throw new \Exception($e->getMessage());
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
    if($this->subscriberCustomFields) {
      $this->addOrUpdateCustomFields(
        ($action === 'create') ? 'create' : 'update',
        $result,
        $subscribersData
      );
    }
    return $result;
  }

  function addOrUpdateCustomFields($action, $dbSubscribers, $subscribersData) {
    $subscribers = array_map(
      function ($column) use ($dbSubscribers, $subscribersData) {
        $count = range(0, count($subscribersData[$column]) - 1);
        return array_map(
          function ($index, $value)
          use ($dbSubscribers, $subscribersData, $column) {
            $subscriberId = array_search(
              $subscribersData['s_email'][$index],
              $dbSubscribers
            );
            return array(
              $column,
              $subscriberId,
              $value
            );
          }, $count, $subscribersData[$column]);
      }, $this->subscriberCustomFields)[0];
    foreach (array_chunk($subscribers, 200) as $data) {
      try {
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
      } catch (\PDOException $e) {
        throw new \Exception($e->getMessage());
      }
    }
  }

  function timeExecution() {
    $profilerEnd = microtime(true);
    return ($profilerEnd - $this->profilerStart) / 60;
  }
}