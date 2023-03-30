<?php declare(strict_types = 1);

namespace MailPoet\Test\Models;

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoetVendor\Idiorm\ORM;

class SegmentTest extends \MailPoetTest {
  public $segment;
  public $newslettersData;
  public $subscribersData;
  public $segmentData;

  public function _before() {
    parent::_before();
    $this->segmentData = [
      'name' => 'some name',
      'description' => 'some description',
    ];
    $this->segment = Segment::createOrUpdate($this->segmentData);

    $this->subscribersData = [
      [
        'first_name' => 'John',
        'last_name' => 'Mailer',
        'status' => Subscriber::STATUS_UNSUBSCRIBED,
        'email' => 'john@mailpoet.com',
      ],
      [
        'first_name' => 'Mike',
        'last_name' => 'Smith',
        'status' => Subscriber::STATUS_SUBSCRIBED,
        'email' => 'mike@maipoet.com',
      ],
      [
        'first_name' => 'Dave',
        'last_name' => 'Brown',
        'status' => Subscriber::STATUS_UNCONFIRMED,
        'email' => 'dave@maipoet.com',
      ],
      [
        'first_name' => 'Bob',
        'last_name' => 'Jones',
        'status' => Subscriber::STATUS_BOUNCED,
        'email' => 'bob@maipoet.com',
      ],
    ];
    $this->newslettersData = [
      [
        'subject' => 'My first newsletter',
        'type' => 'standard',
      ],
      [
        'subject' => 'My second newsletter',
        'type' => 'standard',
      ],
    ];
  }

  public function testItCanBeCreated() {
    expect($this->segment->id() > 0)->true();
    expect($this->segment->getErrors())->false();
  }

  public function testItCanHaveName() {
    expect($this->segment->name)->equals($this->segmentData['name']);
  }

  public function nameMustBeUnique() {
    $segment = Segment::create();
    $segment->hydrate($this->segmentData);
    $result = $segment->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals(
      'Another record already exists. Please specify a different "name".'
    );
  }

  public function testItCanHaveDescription() {
    expect($this->segment->description)->equals($this->segmentData['description']);
  }

  public function testItHasToBeValid() {
    $invalidSegment = Segment::create();

    $result = $invalidSegment->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals('Please specify a name.');
  }

  public function testItHasACreatedAtOnCreation() {
    $segment = Segment::findOne($this->segment->id);
    $this->assertInstanceOf(Segment::class, $segment);
    expect($segment->createdAt)->notNull();
  }

  public function testItHasAnUpdatedAtOnCreation() {
    $segment = Segment::findOne($this->segment->id);
    $this->assertInstanceOf(Segment::class, $segment);
    expect($segment->updatedAt)
      ->equals($segment->createdAt);
  }

  public function testItUpdatesTheUpdatedAtOnUpdate() {
    $segment = Segment::findOne($this->segment->id);
    $this->assertInstanceOf(Segment::class, $segment);
    $createdAt = $segment->createdAt;

    sleep(1);

    $segment->name = 'new name';
    $segment->save();

    $updatedSegment = Segment::findOne($segment->id);
    $this->assertInstanceOf(Segment::class, $updatedSegment);
    expect($updatedSegment->createdAt)->equals($createdAt);
    $isTimeUpdated = (
      $updatedSegment->updatedAt > $updatedSegment->createdAt
    );
    expect($isTimeUpdated)->true();
  }

  public function testItCanCreateOrUpdate() {
    $isCreated = Segment::createOrUpdate([
      'name' => 'new list',
    ]);
    expect($isCreated->id() > 0)->true();
    expect($isCreated->getErrors())->false();

    $segment = Segment::where('name', 'new list')
      ->findOne();
    $this->assertInstanceOf(Segment::class, $segment);
    expect($segment->name)->equals('new list');

    $isUpdated = Segment::createOrUpdate(
      [
        'id' => $segment->id,
        'name' => 'updated list',
      ]);
    $segment = Segment::where('name', 'updated list')
      ->findOne();
    $this->assertInstanceOf(Segment::class, $segment);
    expect($segment->name)->equals('updated list');
  }

  public function testItCanHaveManySubscribers() {
    foreach ($this->subscribersData as $subscriberData) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate($subscriberData);
      $subscriber->save();
      $association = SubscriberSegment::create();
      $association->subscriberId = $subscriber->id;
      $association->segmentId = $this->segment->id;
      $association->save();
    }
    $segment = Segment::findOne($this->segment->id);
    $this->assertInstanceOf(Segment::class, $segment);
    $subscribers = $segment->subscribers()
      ->findArray();

    expect(count($subscribers))->equals(4);
  }

  public function testItCanHaveManyNewsletters() {
    foreach ($this->newslettersData as $newsletterData) {
      $newsletter = Newsletter::create();
      $newsletter->hydrate($newsletterData);
      $newsletter->save();
      $association = NewsletterSegment::create();
      $association->newsletterId = $newsletter->id;
      $association->segmentId = $this->segment->id;
      $association->save();
    }
    $segment = Segment::findOne($this->segment->id);
    $this->assertInstanceOf(Segment::class, $segment);
    $newsletters = $segment->newsletters()
      ->findArray();

    expect(count($newsletters))->equals(2);
  }

  public function testItCanHaveSubscriberCount() {
    // normal subscribers
    foreach ($this->subscribersData as $subscriberData) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate($subscriberData);
      $subscriber->save();
      $association = SubscriberSegment::create();
      $association->subscriberId = $subscriber->id;
      $association->segmentId = $this->segment->id;
      $association->status = Subscriber::STATUS_SUBSCRIBED;
      $association->save();
    }

    $this->segment->withSubscribersCount();
    $subscribersCount = $this->segment->subscribers_count;
    expect($subscribersCount[Subscriber::STATUS_SUBSCRIBED])->equals(1);
    expect($subscribersCount[Subscriber::STATUS_UNSUBSCRIBED])->equals(1);
    expect($subscribersCount[Subscriber::STATUS_UNCONFIRMED])->equals(1);
    expect($subscribersCount[Subscriber::STATUS_BOUNCED])->equals(1);

    // unsubscribed from this particular segment
    foreach ($this->subscribersData as $subscriberData) {
      $subscriber = Subscriber::findOne($subscriberData['email']);
      SubscriberSegment::unsubscribeFromSegments($subscriber, [$this->segment->id]);
    }

    $this->segment->withSubscribersCount();
    $subscribersCount = $this->segment->subscribers_count;
    expect($subscribersCount[Subscriber::STATUS_SUBSCRIBED])->equals(0);
    expect($subscribersCount[Subscriber::STATUS_UNSUBSCRIBED])->equals(4);
    expect($subscribersCount[Subscriber::STATUS_UNCONFIRMED])->equals(0);
    expect($subscribersCount[Subscriber::STATUS_BOUNCED])->equals(0);

    // trashed subscribers
    foreach ($this->subscribersData as $subscriberData) {
      $subscriber = Subscriber::findOne($subscriberData['email']);
      SubscriberSegment::resubscribeToAllSegments($subscriber);
      $subscriber->trash();
    }

    $this->segment->withSubscribersCount();
    $subscribersCount = $this->segment->subscribers_count;
    expect($subscribersCount[Subscriber::STATUS_SUBSCRIBED])->equals(0);
    expect($subscribersCount[Subscriber::STATUS_UNSUBSCRIBED])->equals(0);
    expect($subscribersCount[Subscriber::STATUS_UNCONFIRMED])->equals(0);
    expect($subscribersCount[Subscriber::STATUS_BOUNCED])->equals(0);
  }

  public function testItCanGetSegmentsWithSubscriberCount() {
    foreach ($this->subscribersData as $subscriberData) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate($subscriberData);
      $subscriber->save();
      $association = SubscriberSegment::create();
      $association->subscriberId = $subscriber->id;
      $association->segmentId = $this->segment->id;
      $association->save();
    }
    $segments = Segment::getSegmentsWithSubscriberCount();
    expect($segments[0]['subscribers'])->equals(1);
  }

  public function testItCanGetSegmentsForExport() {
    foreach ($this->subscribersData as $index => $subscriberData) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate($subscriberData);
      $subscriber->save();
      if (!$index) {
        $association = SubscriberSegment::create();
        $association->subscriberId = $subscriber->id;
        $association->segmentId = $this->segment->id;
        $association->status = Subscriber::STATUS_SUBSCRIBED;
        $association->save();
      }
    }
    $segments = Segment::getSegmentsForExport();
    expect($segments[1]['name'])->equals('Subscribers without a list');
    expect($segments[1]['subscribers'])->equals(3);
    expect($segments[0]['name'])->equals($this->segmentData['name']);
    expect($segments[0]['subscribers'])->equals(1);
  }

  public function _after() {
    parent::_after();
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterSegment::$_table);
  }
}
