<?php
namespace MailPoet\Queue;

use Carbon\Carbon;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Queue {
  function __construct() {
    $this->checkRequestMethod();
    set_time_limit(0);
    ignore_user_abort();
    ob_start();
    list ($this->queue, $this->queueData) = $this->getQueue();
  }

  function start() {
    $_start = function ($queue, $queueData) {
      $queue->value = serialize($queueData);
      $queue->updated_at = date('Y-m-d H:i:s');
      $queue->save();
      $this->setHeadersAndFlush($queueData['status']);
      $this->callSelf();
    };
    if(!$this->queue) {
      $queue = Setting::create();
      $queueData = array(
        'status' => 'started',
        'executionCounter' => 0
      );
      $queue->name = 'queue';
      $_start($queue, $queueData);
    }
    $queue = $this->queue;
    $queueData = $this->queueData;
    if($queueData['status'] === 'stopped' || $queueData['status'] === 'paused') {
      if($queueData['status'] !== 'paused') $queueData['executionCounter'] = 0;
      $queueData['status'] = 'started';
      $_start($queue, $queueData);
    }
    $this->setHeadersAndFlush('already running', true);
  }

  function process() {
    if(!$this->queue) {
      $this->setHeadersAndFlush('not running', true);
    }
    $queue = $this->queue;
    $queueData = $this->queueData;
    if($queueData['status'] !== 'started') {
      $this->setHeadersAndFlush('not running', true);
    }
    // TODO: check if the queue is already being executed
      $currentTime = Carbon::now('UTC');
      $lastUpdateTime = Carbon::createFromFormat('Y-m-d H:i:s', $queue->updated_at, 'UTC');
      $timeSinceLastStart = $currentTime->diffInSeconds($lastUpdateTime);
      $this->setHeadersAndFlush('processing');
    sleep(30); // THIS WILL BE REPLACED BY SENDING LOGIC
    list($queue, $queueData) = $this->getQueue();
    $queueData['executionCounter']++;
    $queue->value = serialize($queueData);
    $queue->save();
    // TODO: remove
        $setting = Setting::create();
        $setting->name = date('H:i:s');
        $setting->save();
    $this->callSelf();
  }

  function pause() {
    $this->updateQueue('paused');
  }

  function stop() {
    $this->updateQueue('stopped');
  }

  function updateQueue($status = false) {
    if (!$status) return;
    if(!$this->queue || $this->queueData['status'] !== 'started') {
      $this->setHeadersAndFlush('not running', true);
    }
    $queue = $this->queue;
    $queueData = $this->queueData;
    $queueData['status'] = $status;
    $queue->value = serialize($queueData);
    $queue->save();
    $this->setHeadersAndFlush($queueData['status'], true);
  }

  function setHeadersAndFlush($status, $terminate = false) {
    ob_end_clean();
    header('Connection: close');
    header('X-MailPoet-Queue: ' . $status);
    ob_end_flush();
    ob_flush();
    flush();
    if($terminate) exit;
  }

  function callSelf() {
    stream_context_set_default(array('http' => array('method' => 'HEAD')));
    get_headers(home_url() . '/?mailpoet-api&section=queue&action=process', 1);
    exit;
  }

  function getQueue() {
    $queue = Setting::where('name', 'queue')
      ->findOne();
    $queueData = ($queue) ? unserialize($queue->value) : false;
    return array(
      $queue,
      $queueData
    );
  }

  function checkRequestMethod() {
    $method = $_SERVER['REQUEST_METHOD'];
    if($method !== 'HEAD') {
      header('HTTP/1.0 405 Method Not Allowed');
      exit;
    }
  }
}