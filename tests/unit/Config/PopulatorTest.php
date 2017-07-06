<?php

use MailPoet\Config\Populator;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;

class PopulatorTest extends MailPoetTest {
  function testItPopulatesNewslettersTableSentAtColumn() {
    // TODO: remove in final release
    $newsletters = array();
    for($i = 1; $i <= 3; $i++) {
      $newsletters[$i] = Newsletter::create();
      $newsletters[$i]->type = Newsletter::TYPE_STANDARD;
      $newsletters[$i]->save();
    }
    expect(Newsletter::whereNull('sent_at')->findMany())->count(3);

    $sending_queue = SendingQueue::create();
    $sending_queue->newsletter_id = $newsletters[1]->id;
    $sending_queue->processed_at = date( 'Y-m-d H:i:s');
    $sending_queue->save();

    $populator = new Populator();
    $populator->populateNewsletterSentAtField();
    expect(Newsletter::whereNull('sent_at')->findMany())->count(2);
    expect(Newsletter::whereNotNull('sent_at')->findMany())->count(1);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}