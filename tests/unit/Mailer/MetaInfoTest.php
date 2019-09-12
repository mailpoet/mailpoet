<?php
namespace MailPoet\Test\Mailer;

use Codeception\Stub;
use MailPoet\Mailer\MetaInfo;

class MetaInfoTest extends \MailPoetUnitTest {
  /** @var MetaInfo */
  private $meta;

  function _before() {
    $this->meta = new MetaInfo;
  }

  function testItGetsMetaInfoForSendingTest() {
    expect($this->meta->getSendingTestMetaInfo())->equals([
      'email_type' => 'sending_test',
      'subscriber_status' => 'unknown',
      'subscriber_source' => 'administrator',
    ]);
  }

  function testItGetsMetaInfoForPreview() {
    expect($this->meta->getPreviewMetaInfo())->equals([
      'email_type' => 'preview',
      'subscriber_status' => 'unknown',
      'subscriber_source' => 'administrator',
    ]);
  }
}
