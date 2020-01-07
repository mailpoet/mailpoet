<?php

namespace MailPoet\Test\Subscription;

use Codeception\Stub;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscription\Manage;
use MailPoet\Util\Url as UrlHelper;

class ManageTest extends \MailPoetTest {

  private $settings;
  private $segment_a;
  private $segment_b;
  private $hidden_segment;
  private $subscriber;

  public function _before() {
    parent::_before();
    $di = $this->di_container;
    $this->settings = $di->get(SettingsController::class);
    $this->segment_a = Segment::createOrUpdate(['name' => 'List A']);
    $this->segment_b = Segment::createOrUpdate(['name' => 'List B']);
    $this->hidden_segment = Segment::createOrUpdate(['name' => 'Hidden List']);
    $this->settings->set('subscription.segments', [$this->segment_a->id, $this->segment_b->id]);
    $this->subscriber = Subscriber::createOrUpdate([
      'first_name' => 'John',
      'last_name' => 'John',
      'email' => 'john.doe@example.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [$this->segment_a->id, $this->hidden_segment->id],
    ]);
  }

  public function testItDoesntRemoveHiddenSegments() {
    $di = $this->di_container;
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
      $this->settings
    );
    $_POST['action'] = 'mailpoet_subscription_update';
    $_POST['token'] = 'token';
    $_POST['data'] = [
      'first_name' => 'John',
      'last_name' => 'John',
      'email' => 'john.doe@example.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [$this->segment_b->id],
    ];

    $manage->onSave();

    $subscriber = Subscriber::findOne($this->subscriber->id);
    $subscriber->withSubscriptions();
    $subscriptions = array_map(function($s) {
      return ['status' => $s['status'], 'segment_id' => $s['segment_id']];
    }, $subscriber->subscriptions);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscriptions)->equals([
      ['segment_id' => $this->segment_a->id, 'status' => Subscriber::STATUS_UNSUBSCRIBED],
      ['segment_id' => $this->hidden_segment->id, 'status' => Subscriber::STATUS_SUBSCRIBED],
      ['segment_id' => $this->segment_b->id, 'status' => Subscriber::STATUS_SUBSCRIBED],
    ]);
  }

}
