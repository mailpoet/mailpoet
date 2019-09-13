<?php
namespace MailPoet\Test\Mailer;

use Codeception\Stub;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Models\Subscriber;

class MetaInfoTest extends \MailPoetTest {
  /** @var MetaInfo */
  private $meta;

  function _before() {
    parent::_before();
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

  function testItGetsMetaInfoForStatsNotifications() {
    expect($this->meta->getStatsNotificationMetaInfo())->equals([
      'email_type' => 'email_stats_notification',
      'subscriber_status' => 'unknown',
      'subscriber_source' => 'administrator',
    ]);
  }

  function testItGetsMetaInfoForWordPressTransactionalEmails() {
    expect($this->meta->getWordPressTransactionalMetaInfo())->equals([
      'email_type' => 'transactional',
      'subscriber_status' => 'unknown',
      'subscriber_source' => 'administrator',
    ]);
  }

  function testItGetsMetaInfoForConfirmationEmails() {
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'meta@test.fake',
      'status' => 'unconfirmed',
      'source' => 'form',
    ]);
    expect($this->meta->getConfirmationMetaInfo($subscriber))->equals([
      'email_type' => 'confirmation',
      'subscriber_status' => 'unconfirmed',
      'subscriber_source' => 'form',
    ]);
  }

  function testItGetsMetaInfoForNewSubscriberNotifications() {
    expect($this->meta->getNewSubscriberNotificationMetaInfo())->equals([
      'email_type' => 'new_subscriber_notification',
      'subscriber_status' => 'unknown',
      'subscriber_source' => 'administrator',
    ]);
  }

  function _after() {
    Subscriber::deleteMany();
  }
}
