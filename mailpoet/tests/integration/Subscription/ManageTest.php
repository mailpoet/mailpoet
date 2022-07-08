<?php

namespace MailPoet\Test\Subscription;

use Codeception\Stub;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\Manage;
use MailPoet\Util\Url as UrlHelper;
use MailPoetVendor\Idiorm\ORM;

class ManageTest extends \MailPoetTest {

  private $settings;
  private $segmentA;
  private $segmentB;
  private $hiddenSegment;
  private $subscriber;

  public function _before() {
    parent::_before();
    $this->_after();
    $di = $this->diContainer;
    $this->settings = $di->get(SettingsController::class);
    $this->segmentA = Segment::createOrUpdate(['name' => 'List A']);
    $this->segmentB = Segment::createOrUpdate(['name' => 'List B']);
    $this->hiddenSegment = Segment::createOrUpdate(['name' => 'Hidden List']);
    $this->settings->set('subscription.segments', [$this->segmentA->id, $this->segmentB->id]);
    $this->subscriber = Subscriber::createOrUpdate([
      'first_name' => 'John',
      'last_name' => 'John',
      'email' => 'john.doe@example.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [$this->segmentA->id, $this->hiddenSegment->id],
    ]);
  }

  public function testItDoesntRemoveHiddenSegmentsAndCanResubscribe() {
    $manage = new Manage(
      Stub::make(UrlHelper::class, [
        'redirectBack' => null,
      ]),
      Stub::make(FieldNameObfuscator::class, [
        'deobfuscateFormPayload' => function($data) {
          return $data;
        },
      ]),
      Stub::make(LinkTokens::class, [
        'verifyToken' => function($token) {
          return true;
        },
      ]),
      $this->diContainer->get(Unsubscribes::class),
      $this->settings,
      $this->diContainer->get(NewSubscriberNotificationMailer::class),
      $this->diContainer->get(WelcomeScheduler::class),
      $this->diContainer->get(SegmentsRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriberSegmentRepository::class)
    );
    $_POST['action'] = 'mailpoet_subscription_update';
    $_POST['token'] = 'token';
    $_POST['data'] = [
      'first_name' => 'John',
      'last_name' => 'John',
      'email' => 'john.doe@example.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [$this->segmentB->id],
    ];

    $manage->onSave();

    $subscriber = Subscriber::findOne($this->subscriber->id);
    $subscriber->withSubscriptions();
    $subscriptions = array_map(function($s) {
      return ['status' => $s['status'], 'segment_id' => $s['segment_id']];
    }, $subscriber->subscriptions);
    usort($subscriptions, function($a, $b) {
      return $a['segment_id'] - $b['segment_id'];
    });
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscriptions)->equals([
      ['segment_id' => $this->segmentA->id, 'status' => Subscriber::STATUS_UNSUBSCRIBED],
      ['segment_id' => $this->segmentB->id, 'status' => Subscriber::STATUS_SUBSCRIBED],
      ['segment_id' => $this->hiddenSegment->id, 'status' => Subscriber::STATUS_SUBSCRIBED],
    ]);

    // Test it can resubscribe
    $_POST['data']['segments'] = [$this->segmentA->id];
    $manage->onSave();

    $subscriber = Subscriber::findOne($this->subscriber->id);
    $subscriber->withSubscriptions();
    $subscriptions = array_map(function($s) {
      return ['status' => $s['status'], 'segment_id' => $s['segment_id']];
    }, $subscriber->subscriptions);
    usort($subscriptions, function($a, $b) {
      return $a['segment_id'] - $b['segment_id'];
    });
    expect($subscriptions)->equals([
      ['segment_id' => $this->segmentA->id, 'status' => Subscriber::STATUS_SUBSCRIBED],
      ['segment_id' => $this->segmentB->id, 'status' => Subscriber::STATUS_UNSUBSCRIBED],
      ['segment_id' => $this->hiddenSegment->id, 'status' => Subscriber::STATUS_SUBSCRIBED],
    ]);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
  }
}
