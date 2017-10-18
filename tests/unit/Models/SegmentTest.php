<?php
namespace MailPoet\Test\Models;

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;

class SegmentTest extends \MailPoetTest {
  function _before() {
    $this->data = array(
      'name' => 'some name',
      'description' => 'some description'
    );
    $this->segment = Segment::createOrUpdate($this->data);

    $this->subscribers_data = array(
      array(
        'first_name' => 'John',
        'last_name' => 'Mailer',
        'status' => Subscriber::STATUS_UNSUBSCRIBED,
        'email' => 'john@mailpoet.com'
      ),
      array(
        'first_name' => 'Mike',
        'last_name' => 'Smith',
        'status' => Subscriber::STATUS_SUBSCRIBED,
        'email' => 'mike@maipoet.com'
      ),
      array(
        'first_name' => 'Dave',
        'last_name' => 'Brown',
        'status' => Subscriber::STATUS_UNCONFIRMED,
        'email' => 'dave@maipoet.com'
      ),
      array(
        'first_name' => 'Bob',
        'last_name' => 'Jones',
        'status' => Subscriber::STATUS_BOUNCED,
        'email' => 'bob@maipoet.com'
      )
    );
    $this->newsletters_data = array(
      array(
        'subject' => 'My first newsletter',
        'type' => 'standard'
      ),
      array(
        'subject' => 'My second newsletter',
        'type' => 'standard'
      )
    );
  }

  function testItCanBeCreated() {
    expect($this->segment->id() > 0)->true();
    expect($this->segment->getErrors())->false();
  }

  function testItCanHaveName() {
    expect($this->segment->name)->equals($this->data['name']);
  }

  function nameMustBeUnique() {
    $segment = Segment::create();
    $segment->hydrate($this->data);
    $result = $segment->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals(
      'Another record already exists. Please specify a different "name".'
    );
  }

  function testItCanHaveDescription() {
    expect($this->segment->description)->equals($this->data['description']);
  }

  function testItHasToBeValid() {
    $invalid_segment = Segment::create();

    $result = $invalid_segment->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals('Please specify a name.');
  }

  function testItHasACreatedAtOnCreation() {
    $segment = Segment::findOne($this->segment->id);
    expect($segment->created_at)->notNull();
  }

  function testItHasAnUpdatedAtOnCreation() {
    $segment = Segment::findOne($this->segment->id);
    expect($segment->updated_at)
      ->equals($segment->created_at);
  }

  function testItUpdatesTheUpdatedAtOnUpdate() {
    $segment = Segment::findOne($this->segment->id);
    $created_at = $segment->created_at;

    sleep(1);

    $segment->name = 'new name';
    $segment->save();

    $updated_segment = Segment::findOne($segment->id);
    expect($updated_segment->created_at)->equals($created_at);
    $is_time_updated = (
      $updated_segment->updated_at > $updated_segment->created_at
    );
    expect($is_time_updated)->true();
  }

  function testItCanCreateOrUpdate() {
    $is_created = Segment::createOrUpdate(array(
      'name' => 'new list'
    ));
    expect($is_created->id() > 0)->true();
    expect($is_created->getErrors())->false();

    $segment = Segment::where('name', 'new list')
      ->findOne();
    expect($segment->name)->equals('new list');

    $is_updated = Segment::createOrUpdate(
      array(
        'id' => $segment->id,
        'name' => 'updated list'
      ));
    $segment = Segment::where('name', 'updated list')
      ->findOne();
    expect($segment->name)->equals('updated list');
  }

  function testItCanHaveManySubscribers() {
    foreach($this->subscribers_data as $subscriber_data) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate($subscriber_data);
      $subscriber->save();
      $association = SubscriberSegment::create();
      $association->subscriber_id = $subscriber->id;
      $association->segment_id = $this->segment->id;
      $association->save();
    }
    $segment = Segment::findOne($this->segment->id);
    $subscribers = $segment->subscribers()
      ->findArray();

    expect(count($subscribers))->equals(4);
  }

  function testItCanHaveManyNewsletters() {
    foreach($this->newsletters_data as $newsletter_data) {
      $newsletter = Newsletter::create();
      $newsletter->hydrate($newsletter_data);
      $newsletter->save();
      $association = NewsletterSegment::create();
      $association->newsletter_id = $newsletter->id;
      $association->segment_id = $this->segment->id;
      $association->save();
    }
    $segment = Segment::findOne($this->segment->id);
    $newsletters = $segment->newsletters()
      ->findArray();

    expect(count($newsletters))->equals(2);
  }

  function testItCanHaveSubscriberCount() {
    // normal subscribers
    foreach($this->subscribers_data as $subscriber_data) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate($subscriber_data);
      $subscriber->save();
      $association = SubscriberSegment::create();
      $association->subscriber_id = $subscriber->id;
      $association->segment_id = $this->segment->id;
      $association->status = Subscriber::STATUS_SUBSCRIBED;
      $association->save();
    }

    $this->segment->withSubscribersCount();
    $subscribers_count = $this->segment->subscribers_count;
    expect($subscribers_count[Subscriber::STATUS_SUBSCRIBED])->equals(1);
    expect($subscribers_count[Subscriber::STATUS_UNSUBSCRIBED])->equals(1);
    expect($subscribers_count[Subscriber::STATUS_UNCONFIRMED])->equals(1);
    expect($subscribers_count[Subscriber::STATUS_BOUNCED])->equals(1);

    // unsubscribed from this particular segment
    foreach($this->subscribers_data as $subscriber_data) {
      $subscriber = Subscriber::findOne($subscriber_data['email']);
      SubscriberSegment::unsubscribeFromSegments($subscriber, array($this->segment->id));
    }

    $this->segment->withSubscribersCount();
    $subscribers_count = $this->segment->subscribers_count;
    expect($subscribers_count[Subscriber::STATUS_SUBSCRIBED])->equals(0);
    expect($subscribers_count[Subscriber::STATUS_UNSUBSCRIBED])->equals(4);
    expect($subscribers_count[Subscriber::STATUS_UNCONFIRMED])->equals(0);
    expect($subscribers_count[Subscriber::STATUS_BOUNCED])->equals(0);

    // trashed subscribers
    foreach($this->subscribers_data as $subscriber_data) {
      $subscriber = Subscriber::findOne($subscriber_data['email']);
      SubscriberSegment::resubscribeToAllSegments($subscriber);
      $subscriber->trash();
    }

    $this->segment->withSubscribersCount();
    $subscribers_count = $this->segment->subscribers_count;
    expect($subscribers_count[Subscriber::STATUS_SUBSCRIBED])->equals(0);
    expect($subscribers_count[Subscriber::STATUS_UNSUBSCRIBED])->equals(0);
    expect($subscribers_count[Subscriber::STATUS_UNCONFIRMED])->equals(0);
    expect($subscribers_count[Subscriber::STATUS_BOUNCED])->equals(0);
  }

  function testItCanGetSegmentsWithSubscriberCount() {
    foreach($this->subscribers_data as $subscriber_data) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate($subscriber_data);
      $subscriber->save();
      $association = SubscriberSegment::create();
      $association->subscriber_id = $subscriber->id;
      $association->segment_id = $this->segment->id;
      $association->save();
    }
    $segments = Segment::getSegmentsWithSubscriberCount();
    expect($segments[0]['subscribers'])->equals(1);
  }

  function testItCanGetSegmentsForExport() {
    foreach($this->subscribers_data as $index => $subscriber_data) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate($subscriber_data);
      $subscriber->save();
      if(!$index) {
        $association = SubscriberSegment::create();
        $association->subscriber_id = $subscriber->id;
        $association->segment_id = $this->segment->id;
        $association->status = Subscriber::STATUS_SUBSCRIBED;
        $association->save();
      }
    }
    $segments = Segment::getSegmentsForExport();
    expect(count($segments))->equals(2);
    expect($segments[0]['name'])->equals('Not in a List');
    $segments = Segment::getSegmentsForExport($withConfirmedSubscribers = true);
    expect(count($segments))->equals(1);
  }

  function testListingQuery() {
    Segment::createOrUpdate(array(
      'name' => 'name 2',
      'description' => 'description 2',
      'type' => 'unknown'
    ));
    $query = Segment::listingQuery(array());
    $data = $query->findMany();
    expect($data)->count(1);
    expect($data[0]->name)->equals('some name');
  }

  function testListingQueryWithGroup() {
    $query = Segment::listingQuery(array('group' => 'trash'));
    $data = $query->findMany();
    expect($data)->count(0);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterSegment::$_table);
  }
}
