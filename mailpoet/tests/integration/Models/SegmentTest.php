<?php declare(strict_types = 1);

namespace MailPoet\Test\Models;

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;

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
    verify($this->segment->id() > 0)->true();
    expect($this->segment->getErrors())->false();
  }

  public function testItCanHaveName() {
    verify($this->segment->name)->equals($this->segmentData['name']);
  }

  public function nameMustBeUnique() {
    $segment = Segment::create();
    $segment->hydrate($this->segmentData);
    $result = $segment->save();
    $errors = $result->getErrors();

    verify(is_array($errors))->true();
    verify($errors[0])->equals(
      'Another record already exists. Please specify a different "name".'
    );
  }

  public function testItCanHaveDescription() {
    verify($this->segment->description)->equals($this->segmentData['description']);
  }

  public function testItHasToBeValid() {
    $invalidSegment = Segment::create();

    $result = $invalidSegment->save();
    $errors = $result->getErrors();

    verify(is_array($errors))->true();
    verify($errors[0])->equals('Please specify a name.');
  }

  public function testItHasACreatedAtOnCreation() {
    $segment = Segment::findOne($this->segment->id);
    $this->assertInstanceOf(Segment::class, $segment);
    expect($segment->createdAt)->notNull();
  }

  public function testItHasAnUpdatedAtOnCreation() {
    $segment = Segment::findOne($this->segment->id);
    $this->assertInstanceOf(Segment::class, $segment);
    verify($segment->updatedAt)
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
    verify($updatedSegment->createdAt)->equals($createdAt);
    $isTimeUpdated = (
      $updatedSegment->updatedAt > $updatedSegment->createdAt
    );
    verify($isTimeUpdated)->true();
  }

  public function testItCanCreateOrUpdate() {
    $isCreated = Segment::createOrUpdate([
      'name' => 'new list',
    ]);
    verify($isCreated->id() > 0)->true();
    expect($isCreated->getErrors())->false();

    $segment = Segment::where('name', 'new list')
      ->findOne();
    $this->assertInstanceOf(Segment::class, $segment);
    verify($segment->name)->equals('new list');

    $isUpdated = Segment::createOrUpdate(
      [
        'id' => $segment->id,
        'name' => 'updated list',
      ]);
    $segment = Segment::where('name', 'updated list')
      ->findOne();
    $this->assertInstanceOf(Segment::class, $segment);
    verify($segment->name)->equals('updated list');
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

    verify(count($subscribers))->equals(4);
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

    verify(count($newsletters))->equals(2);
  }
}
