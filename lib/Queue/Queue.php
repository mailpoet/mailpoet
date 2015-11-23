<?php
namespace MailPoet\Queue;

use MailPoet\Models\Setting;
use MailPoet\Util\Security;

require_once(ABSPATH . 'wp-includes/pluggable.php');

if(!defined('ABSPATH')) exit;

class Queue {
  function __construct($payload = array()) {
    set_time_limit(0);
    ignore_user_abort();
    list ($this->queue, $this->queueData) = $this->getQueue();
    $this->refreshedToken = $this->refreshToken();
    $this->payload = $payload;
  }
  
  function start() {
    if(!isset($this->payload['token'])) {
      $this->abortWithError('missing token');
    }
    $queue = $this->queue;
    $queueData = $this->queueData;
    if(!$queue) {
      $queue = Setting::create();
      $queue->name = 'queue';
      $queue->value = serialize(array('status' => 'stopped'));
      $queue->save();
    }
    if(!preg_match('!stopped|paused!', $queueData['status'])
    ) {
      $queueData = array(
        'status' => 'started',
        'token' => $this->refreshedToken,
        'executionCounter' => ($queueData['status'] === 'paused') ?
          $queueData['executionCounter']
          : 0,
        'log' => array(
          'token' => $this->payload['token'],
          'message' => 'started'
        )
      );
      $queue->value = serialize($queueData);
      $queue->save();
      $this->callSelf();
    } else {
      $queueData['log'] = array(
        'token' => $this->payload['token'],
        'status' => 'already started'
      );
      $queue->value = serialize($queueData);
      $queue->save();
    }
  }
  
  function process() {
    if(!$this->queue || $this->queueData['status'] !== 'started') {
      $this->abortWithError('not running');
    }
    if(!isset($this->payload['token']) ||
      $this->payload['token'] !== $this->queueData['token']
    ) {
      $this->abortWithError('invalid token');
    }
    
    /*
     * LOGIC WILL HAPPEN HERE
     *
     */
    sleep(30);
    
    // after each execution, read queue in case its status was modified
    list($queue, $queueData) = $this->getQueue();
    $queueData['executionCounter']++;
    $queueData['token'] = $this->refreshedToken;
    $queue->value = serialize($queueData);
    $queue->save();
    $this->callSelf();
  }

  function callSelf() {
    $payload = json_encode(array('token' => $this->refreshedToken));
    $args = array(
      'timeout' => 1,
      'user-agent' => 'MailPoet (www.mailpoet.com)'
    );
    wp_remote_get(
      site_url() .
      '/?mailpoet-api&section=queue&action=process&payload=' . urlencode($payload),
      $args
    );
    exit;
  }
  
  function abortWithError($error) {
    wp_send_json(
      array(
        'result' => false,
        'error' => $error
      ));
    exit;
  }
  
  function getQueue() {
    $queue = Setting::where('name', 'queue')
      ->findOne();
    return array(
      ($queue) ? $queue : null,
      ($queue) ? unserialize($queue->value) : null
    );
  }
  
  function checkAuthorization() {
    if(!current_user_can('manage_options')) {
      header('HTTP/1.0 401 Not Authorized');
      exit;
    }
  }
  
  function refreshToken() {
    return Security::generateRandomString(5);
  }
}