<?php namespace MailPoet\Import;

class Import {
  public function __construct($data) {
    $this->subscribersData = $data['subscribers'];
    $this->segments = $data['segments'];
    $this->updateSubscribers = $data['updateSubscribers'];
    $this->subscriberFields = $this->getSubscriberFields();
    $this->subscriberCustomFields = $this->getCustomSubscriberFields();
    $this->currentTime = time();
    $this->profilerStart = microtime(true);
  }

  function process() {
    // :)
    return array(
      'status' => 'success',
      'count' => count($this->subscribersData['subscriber_email'])
    );
    if(in_array('subscriber_status', $subscriberFields)) {
      $this->subscribersData['subscriber_state'] = $this->filterSubscriberState(
        $this->subscribersData['subscriber_state']
      );
    }
  }

  function getSubscriberFields() {
    return array_map(function ($field) {
      if(!is_int($field)) return $field;
    }, array_keys($this->subscribersData));
  }

  function getCustomSubscriberFields() {
    return array_map(function ($field) {
      if(is_int($field)) return $field;
    }, array_keys($this->subscribersData));
  }

  function filterSubscriberState($data) {
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

    return array_map(function ($state) use ($states) {
      if(in_array(strtolower($state), $states['subscribed'])) {
        return 1;
      }
      if(in_array(strtolower($state), $states['unsubscribed'])) {
        return -1;
      }
      return 1; // make "subscribed" a default state
    }, $data);
  }

  function timeExecution() {
    $profilerEnd = microtime(true);
    return ($profilerEnd - $this->profilerStart) / 60;
  }
}