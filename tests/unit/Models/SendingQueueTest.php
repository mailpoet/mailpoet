<?php
namespace MailPoet\Test\Models;

use AspectMock\Test as Mock;
use MailPoet\Models\SendingQueue;

class SendingQueueTest extends \MailPoetTest {
  function _before() {
    $this->queue = SendingQueue::create();
    $this->queue->save();

    $this->rendered_body = array(
      'html' => 'some html',
      'text' => 'some text'
    );
  }

  function testItCanEncodeEmojisInBody() {
    $mock = Mock::double('MailPoet\WP\Emoji', [
      'encodeForUTF8Column' => function($params) {
        return $params;
      }
    ]);
    $this->queue->encodeEmojisInBody($this->rendered_body);
    $mock->verifyInvokedMultipleTimes('encodeForUTF8Column', 2);
  }

  function testItCanDecodeEmojisInBody() {
    $mock = Mock::double('MailPoet\WP\Emoji', [
      'decodeEntities' => function($params) {
        return $params;
      }
    ]);
    $this->queue->decodeEmojisInBody($this->rendered_body);
    $mock->verifyInvokedMultipleTimes('decodeEntities', 2);
  }

  function _after() {
    Mock::clean();
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
