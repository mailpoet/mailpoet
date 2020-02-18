<?php

namespace MailPoet\Test\Mailer;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Subscriber;

class MetaInfoTest extends \MailPoetTest {
  /** @var MetaInfo */
  private $meta;

  public function _before() {
    parent::_before();
    $this->meta = new MetaInfo;
  }

  public function testItGetsMetaInfoForSendingTest() {
    expect($this->meta->getSendingTestMetaInfo())->equals([
      'email_type' => 'sending_test',
      'subscriber_status' => 'unknown',
      'subscriber_source' => 'administrator',
    ]);
  }

  public function testItGetsMetaInfoForPreview() {
    expect($this->meta->getPreviewMetaInfo())->equals([
      'email_type' => 'preview',
      'subscriber_status' => 'unknown',
      'subscriber_source' => 'administrator',
    ]);
  }

  public function testItGetsMetaInfoForStatsNotifications() {
    expect($this->meta->getStatsNotificationMetaInfo())->equals([
      'email_type' => 'email_stats_notification',
      'subscriber_status' => 'unknown',
      'subscriber_source' => 'administrator',
    ]);
  }

  public function testItGetsMetaInfoForWordPressTransactionalEmails() {
    expect($this->meta->getWordPressTransactionalMetaInfo())->equals([
      'email_type' => 'transactional',
      'subscriber_status' => 'unknown',
      'subscriber_source' => 'unknown',
    ]);

    $subscriber = $this->make(SubscriberEntity::class, [
      'getStatus' => 'subscribed',
      'getSource' => 'form',
    ]);
    expect($this->meta->getWordPressTransactionalMetaInfo($subscriber))->equals([
      'email_type' => 'transactional',
      'subscriber_status' => 'subscribed',
      'subscriber_source' => 'form',
    ]);
  }

  public function testItGetsMetaInfoForConfirmationEmails() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'status' => 'unconfirmed',
      'source' => 'form',
    ]);
    expect($this->meta->getConfirmationMetaInfo($subscriber))->equals([
      'email_type' => 'confirmation',
      'subscriber_status' => 'unconfirmed',
      'subscriber_source' => 'form',
    ]);
  }

  public function testItGetsMetaInfoForNewSubscriberNotifications() {
    expect($this->meta->getNewSubscriberNotificationMetaInfo())->equals([
      'email_type' => 'new_subscriber_notification',
      'subscriber_status' => 'unknown',
      'subscriber_source' => 'administrator',
    ]);
  }

  public function testItGetsMetaInfoForStandardNewsletter() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'status' => 'subscribed',
      'source' => 'form',
    ]);
    $newsletter = (object)[
      'type' => Newsletter::TYPE_STANDARD,
    ];
    expect($this->meta->getNewsletterMetaInfo($newsletter, $subscriber))->equals([
      'email_type' => 'newsletter',
      'subscriber_status' => 'subscribed',
      'subscriber_source' => 'form',
    ]);
  }

  public function testItGetsMetaInfoForWelcomeEmail() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'status' => 'subscribed',
      'source' => 'form',
    ]);
    $newsletter = (object)[
      'type' => Newsletter::TYPE_WELCOME,
    ];
    expect($this->meta->getNewsletterMetaInfo($newsletter, $subscriber))->equals([
      'email_type' => 'welcome',
      'subscriber_status' => 'subscribed',
      'subscriber_source' => 'form',
    ]);
  }

  public function testItGetsMetaInfoForPostNotification() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'status' => 'subscribed',
      'source' => 'form',
    ]);
    $newsletter1 = (object)[
      'type' => Newsletter::TYPE_NOTIFICATION,
    ];
    $newsletter2 = (object)[
      'type' => Newsletter::TYPE_NOTIFICATION_HISTORY,
    ];
    expect($this->meta->getNewsletterMetaInfo($newsletter1, $subscriber))->equals([
      'email_type' => 'post_notification',
      'subscriber_status' => 'subscribed',
      'subscriber_source' => 'form',
    ]);
    expect($this->meta->getNewsletterMetaInfo($newsletter2, $subscriber))->equals([
      'email_type' => 'post_notification',
      'subscriber_status' => 'subscribed',
      'subscriber_source' => 'form',
    ]);
  }

  public function testItGetsMetaInfoForAutomaticEmails() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'status' => 'subscribed',
      'source' => 'form',
    ]);
    $newsletter1 = (object)[
      'type' => Newsletter::TYPE_AUTOMATIC,
      'options' => [
        'group' => 'woocommerce',
        'event' => 'woocommerce_first_purchase',
      ],
    ];
    $newsletter2 = (object)[
      'type' => Newsletter::TYPE_AUTOMATIC,
      'options' => [
        'group' => 'woocommerce',
        'event' => 'woocommerce_purchased_in_category',
      ],
    ];
    expect($this->meta->getNewsletterMetaInfo($newsletter1, $subscriber))->equals([
      'email_type' => 'automatic_woocommerce_woocommerce_first_purchase',
      'subscriber_status' => 'subscribed',
      'subscriber_source' => 'form',
    ]);
    expect($this->meta->getNewsletterMetaInfo($newsletter2, $subscriber))->equals([
      'email_type' => 'automatic_woocommerce_woocommerce_purchased_in_category',
      'subscriber_status' => 'subscribed',
      'subscriber_source' => 'form',
    ]);
  }

  public function testItSetsUnknownSubscriberSourceWhenNull() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'status' => 'subscribed',
      'source' => null,
    ]);
    $newsletter = (object)[
      'type' => Newsletter::TYPE_STANDARD,
    ];
    expect($this->meta->getNewsletterMetaInfo($newsletter, $subscriber))->equals([
      'email_type' => 'newsletter',
      'subscriber_status' => 'subscribed',
      'subscriber_source' => 'unknown',
    ]);
  }

  public function _after() {
    Subscriber::deleteMany();
  }
}
