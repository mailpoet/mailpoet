<?php

class SendingQueueCest {
  function _before() {
    $this->sendingQueue = \MailPoet\Models\SendingQueue::create();
    $this->sendingQueue->save();
  }

  function itHasNullStatusOnCreation() {
    expect($this->sendingQueue->status)->null();
  }

  function itCanBePaused() {
    $result = $this->sendingQueue->pause();
    expect($result)->false();
    $this->sendingQueue->count_total = 1;
    $this->sendingQueue->pause();
    expect($this->sendingQueue->status)->equals('paused');
  }

  function itCanBeResumed() {
    $this->sendingQueue->count_total = 1;
    $this->sendingQueue->resume();
    expect($this->sendingQueue->status)->null();
  }

  function itCanBeCompleted() {
    $this->sendingQueue->complete();
    expect($this->sendingQueue->status)->equals('completed');
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . \MailPoet\Models\SendingQueue::$_table);
  }
}
