<?php

use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;

class SendingQueueModelTest extends MailPoetTest {
  function _before() {
    $this->newsletter = Newsletter::create();
    $this->newsletter->type = Newsletter::TYPE_STANDARD;
    $this->newsletter->save();
    expect(Newsletter::findMany())->count(1);
    $this->sending_queue = SendingQueue::create();
    $this->sending_queue->newsletter_id = $this->newsletter->id;
    $this->sending_queue->save();
    expect(SendingQueue::findMany())->count(1);
  }

  function testItDeletesParentNewsletter() {
    $this->sending_queue->delete();
    expect(Newsletter::findMany())->count(0);
    expect(SendingQueue::findMany())->count(0);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}