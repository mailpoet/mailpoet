<?php

use \MailPoet\Router\Subscribers;
use \MailPoet\Models\Subscriber;
use \MailPoet\Models\Segment;

class SubscribersTest extends MailPoetTest {
  function _before() {
    $this->segment_1 = Segment::createOrUpdate(array('name' => 'Segment 1'));
    $this->segment_2 = Segment::createOrUpdate(array('name' => 'Segment 2'));

    $this->subscriber_1 = Subscriber::createOrUpdate(array(
      'email' => 'john@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_UNCONFIRMED
    ));
    $this->subscriber_2 = Subscriber::createOrUpdate(array(
      'email' => 'jane@mailpoet.com',
      'first_name' => 'Jane',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => array(
        $this->segment_1->id(),
        $this->segment_2->id()
      )
    ));
  }

  function testItCanGetASubscriber() {
    $router = new Subscribers();

    $response = $router->get($this->subscriber_1->id());
    expect($response['id'])->equals($this->subscriber_1->id());
    expect($response['email'])->equals($this->subscriber_1->email);
    expect($response['first_name'])->equals($this->subscriber_1->first_name);
    expect($response['last_name'])->equals($this->subscriber_1->last_name);

    $response = $router->get('not_an_id');
    expect($response)->false();

    $response = $router->get(/* missing argument */);
    expect($response)->false();
  }

  function testItCanSaveANewSubscriber() {
    $valid_data = array(
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Raul',
      'last_name' => 'Doe',
      'segments' => array(
        $this->segment_1->id(),
        $this->segment_2->id()
      )
    );

    $router = new Subscribers();
    $response = $router->save($valid_data);
    expect($response['result'])->true();
    expect($response)->hasntKey('errors');

    $subscriber = Subscriber::where('email', 'raul.doe@mailpoet.com')->findOne();
    $subscriber_segments = $subscriber->segments()->findMany();
    expect($subscriber_segments)->count(2);
    expect($subscriber_segments[0]->name)->equals($this->segment_1->name);
    expect($subscriber_segments[1]->name)->equals($this->segment_2->name);

    $response = $router->save(/* missing data */);
    expect($response['result'])->false();
    expect($response['errors'][0])->equals('Please enter your email address.');

    $invalid_data = array(
      'email' => 'john.doe@invalid'
    );

    $response = $router->save($invalid_data);
    expect($response['result'])->false();
    expect($response['errors'][0])->equals('Your email address is invalid.');
  }

  function testItCanSaveAnExistingSubscriber() {
    $router = new Subscribers();
    $subscriber_data = $this->subscriber_2->asArray();
    unset($subscriber_data['created_at']);
    $subscriber_data['segments'] = array($this->segment_1->id());
    $subscriber_data['first_name'] = 'Super Jane';

    $response = $router->save($subscriber_data);
    expect($response['result'])->true();

    $updated_subscriber = Subscriber::findOne($this->subscriber_2->id());
    expect($updated_subscriber->email)->equals($this->subscriber_2->email);
    expect($updated_subscriber->first_name)->equals('Super Jane');
  }

  function testItCanRestoreASubscriber() {
    $this->subscriber_1->trash();

    expect($this->subscriber_1->deleted_at)->notNull();

    $router = new Subscribers();
    $router->restore($this->subscriber_1->id());

    $restored_subscriber = Subscriber::findOne($this->subscriber_1->id());
    expect($restored_subscriber->deleted_at)->null();
  }

  function testItCanTrashASubscriber() {
    $router = new Subscribers();
    $response = $router->trash($this->subscriber_2->id());
    expect($response)->true();

    $trashed_subscriber = Subscriber::findOne($this->subscriber_2->id());
    expect($trashed_subscriber->deleted_at)->notNull();
  }

  function testItCanDeleteASubscriber() {
    $router = new Subscribers();
    $response = $router->delete($this->subscriber_1->id());
    expect($response)->equals(1);

    expect(Subscriber::findOne($this->subscriber_1->id()))->false();
  }

  function testItCanFilterListing() {
    $router = new Subscribers();

    // filter by non existing segment
    $response = $router->listing(array(
      'filter' => array(
        'segment' => '### invalid_segment_id ###'
      )
    ));

    // it should return all subscribers
    expect($response['count'])->equals(2);

    // filter by 1st segment
    $response = $router->listing(array(
      'filter' => array(
        'segment' => $this->segment_1->id
      )
    ));

    expect($response['count'])->equals(1);
    expect($response['items'][0]['email'])->equals($this->subscriber_2->email);

    // filter by 2nd segment
    $response = $router->listing(array(
      'filter' => array(
        'segment' => $this->segment_2->id
      )
    ));

    expect($response['count'])->equals(1);
    expect($response['items'][0]['email'])->equals($this->subscriber_2->email);
  }

  function testItCanSearchListing() {
    $new_subscriber =  Subscriber::createOrUpdate(array(
      'email' => 'search.me@find.me',
      'first_name' => 'Billy Bob',
      'last_name' => 'Thornton'
    ));

    $router = new Subscribers();

    // empty search returns everything
    $response = $router->listing(array(
      'search' => ''
    ));
    expect($response['count'])->equals(3);

    // search by email
    $response = $router->listing(array(
      'search' => '.me'
    ));
    expect($response['count'])->equals(1);
    expect($response['items'][0]['email'])->equals($new_subscriber->email);

    // search by last name
    $response = $router->listing(array(
      'search' => 'doe'
    ));
    expect($response['count'])->equals(2);
    expect($response['items'][0]['email'])->equals($this->subscriber_1->email);
    expect($response['items'][1]['email'])->equals($this->subscriber_2->email);

    // search by first name
    $response = $router->listing(array(
      'search' => 'billy'
    ));
    expect($response['count'])->equals(1);
    expect($response['items'][0]['email'])->equals($new_subscriber->email);
  }

  function testItCanGroupListing() {
    $router = new Subscribers();

    $subscribed_group = $router->listing(array(
      'group' => Subscriber::STATUS_SUBSCRIBED
    ));
    expect($subscribed_group['count'])->equals(1);
    expect($subscribed_group['items'][0]['email'])->equals(
      $this->subscriber_2->email
    );

    $unsubscribed_group = $router->listing(array(
      'group' => Subscriber::STATUS_UNSUBSCRIBED
    ));
    expect($unsubscribed_group['count'])->equals(0);

    $unconfirmed_group = $router->listing(array(
      'group' => Subscriber::STATUS_UNCONFIRMED
    ));
    expect($unconfirmed_group['count'])->equals(1);
    expect($unconfirmed_group['items'][0]['email'])->equals(
      $this->subscriber_1->email
    );

    $trashed_group = $router->listing(array(
      'group' => 'trash'
    ));
    expect($trashed_group['count'])->equals(0);

    // trash 1st subscriber
    $this->subscriber_1->trash();

    $trashed_group = $router->listing(array(
      'group' => 'trash'
    ));
    expect($trashed_group['count'])->equals(1);
    expect($trashed_group['items'][0]['email'])->equals(
      $this->subscriber_1->email
    );
  }

  function testItCanSortAndLimitListing() {
    $router = new Subscribers();
    // get 1st page (limit items per page to 1)
    $response = $router->listing(array(
      'limit' => 1,
      'sort_by' => 'first_name',
      'sort_order' => 'asc'
    ));

    expect($response['count'])->equals(2);
    expect($response['items'])->count(1);
    expect($response['items'][0]['email'])->equals(
      $this->subscriber_2->email
    );

    // get 1st page (limit items per page to 1)
    $response = $router->listing(array(
      'limit' => 1,
      'offset' => 1,
      'sort_by' => 'first_name',
      'sort_order' => 'asc'
    ));

    expect($response['count'])->equals(2);
    expect($response['items'])->count(1);
    expect($response['items'][0]['email'])->equals(
      $this->subscriber_1->email
    );
  }

  function testItCanBulkDeleteSelectionOfSubscribers() {
    $deletable_subscriber =  Subscriber::createOrUpdate(array(
      'email' => 'to.be.removed@mailpoet.com'
    ));

    $selection_ids = array(
      $this->subscriber_1->id,
      $deletable_subscriber->id
    );

    $router = new Subscribers();
    $response = $router->bulkAction(array(
      'listing' => array(
        'selection' => $selection_ids
      ),
      'action' => 'delete'
    ));

    expect($response)->equals(count($selection_ids));

    $is_subscriber_1_deleted = (
      Subscriber::findOne($this->subscriber_1->id) === false
    );
    $is_deletable_subscriber_deleted = (
      Subscriber::findOne($deletable_subscriber->id) === false
    );

    expect($is_subscriber_1_deleted)->true();
    expect($is_deletable_subscriber_deleted)->true();
  }

  function testItCanBulkDeleteSubscribers() {
    expect(Subscriber::count())->equals(2);

    $subscribers = Subscriber::findMany();
    foreach($subscribers as $subscriber) {
      $subscriber->trash();
    }

    $router = new Subscribers();
    $response = $router->bulkAction(array(
      'action' => 'delete',
      'listing' => array('group' => 'trash')
    ));
    expect($response)->equals(2);

    $response = $router->bulkAction(array(
      'action' => 'delete',
      'listing' => array('group' => 'trash')
    ));
    expect($response)->equals(0);
  }

  function testItCannotRunAnInvalidBulkAction() {
    try {
      $router = new Subscribers();
      $response = $router->bulkAction(array(
        'action' => 'invalidAction',
        'listing' => array()
      ));
      $this->fail('Bulk Action class did not throw an exception');
    } catch(Exception $e) {
      expect($e->getMessage())->equals(
        '\MailPoet\Models\Subscriber has not method "bulkInvalidAction"'
      );
    }
  }

  function _after() {
    Segment::deleteMany();
    Subscriber::deleteMany();
  }
}
