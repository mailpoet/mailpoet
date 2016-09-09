<?php

use \MailPoet\API\Endpoints\Subscribers;
use \MailPoet\API\Response as APIResponse;
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
        $this->segment_1->id,
        $this->segment_2->id
      )
    ));
  }

  function testItCanGetASubscriber() {
    $router = new Subscribers();

    $response = $router->get(array('id' => 'not_an_id'));
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals(
      'This subscriber does not exist.'
    );

    $response = $router->get(/* missing argument */);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals(
      'This subscriber does not exist.'
    );

    $response = $router->get(array('id' => $this->subscriber_1->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber_1->id)
        ->withCustomFields()
        ->withSubscriptions()
        ->asArray()
    );
  }

  function testItCanSaveANewSubscriber() {
    $valid_data = array(
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Raul',
      'last_name' => 'Doe',
      'segments' => array(
        $this->segment_1->id,
        $this->segment_2->id
      )
    );

    $router = new Subscribers();
    $response = $router->save($valid_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::where('email', 'raul.doe@mailpoet.com')
        ->findOne()
        ->asArray()
    );

    $subscriber = Subscriber::where('email', 'raul.doe@mailpoet.com')->findOne();
    $subscriber_segments = $subscriber->segments()->findMany();
    expect($subscriber_segments)->count(2);
    expect($subscriber_segments[0]->name)->equals($this->segment_1->name);
    expect($subscriber_segments[1]->name)->equals($this->segment_2->name);

    $response = $router->save(/* missing data */);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])
      ->equals('Please enter your email address');

    $invalid_data = array(
      'email' => 'john.doe@invalid'
    );

    $response = $router->save($invalid_data);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])
      ->equals('Your email address is invalid!');
  }

  function testItCanSaveAnExistingSubscriber() {
    $router = new Subscribers();
    $subscriber_data = $this->subscriber_2->asArray();
    unset($subscriber_data['created_at']);
    $subscriber_data['segments'] = array($this->segment_1->id);
    $subscriber_data['first_name'] = 'Super Jane';

    $response = $router->save($subscriber_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber_2->id)->asArray()
    );
    expect($response->data['first_name'])->equals('Super Jane');
  }

  function testItCanRestoreASubscriber() {
    $this->subscriber_1->trash();

    $trashed_subscriber = Subscriber::findOne($this->subscriber_1->id);
    expect($trashed_subscriber->deleted_at)->notNull();

    $router = new Subscribers();
    $response = $router->restore(array('id' => $this->subscriber_1->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber_1->id)->asArray()
    );
    expect($response->data['deleted_at'])->null();
    expect($response->meta['count'])->equals(1);
  }

  function testItCanTrashASubscriber() {
    $router = new Subscribers();
    $response = $router->trash(array('id' => $this->subscriber_2->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber_2->id)->asArray()
    );
    expect($response->data['deleted_at'])->notNull();
    expect($response->meta['count'])->equals(1);
  }

  function testItCanDeleteASubscriber() {
    $router = new Subscribers();
    $response = $router->delete(array('id' => $this->subscriber_1->id));
    expect($response->data)->isEmpty();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(1);
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
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->isEmpty();
    expect($response->meta['count'])->equals(count($selection_ids));

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
    $router = new Subscribers();
    $response = $router->bulkAction(array(
      'action' => 'trash',
      'listing' => array('group' => 'all')
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(2);

    $router = new Subscribers();
    $response = $router->bulkAction(array(
      'action' => 'delete',
      'listing' => array('group' => 'trash')
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(2);

    $response = $router->bulkAction(array(
      'action' => 'delete',
      'listing' => array('group' => 'trash')
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(0);
  }

  function testItCannotRunAnInvalidBulkAction() {
    $router = new Subscribers();
    $response = $router->bulkAction(array(
      'action' => 'invalidAction',
      'listing' => array()
    ));
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains('has no method');
  }

  function _after() {
    Segment::deleteMany();
    Subscriber::deleteMany();
  }
}
