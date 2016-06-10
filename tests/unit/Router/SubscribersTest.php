<?php

use \MailPoet\Router\Subscribers;
use \MailPoet\Models\Subscriber;
use \MailPoet\Models\Segment;

class SubscribersTest extends MailPoetTest {
  function _before() {
    $this->segment_1 = Segment::createOrUpdate(array('name' => 'Segment 1'));
    $this->segment_2 = Segment::createOrUpdate(array('name' => 'Segment 2'));

    $this->subscriber_1 = Subscriber::createOrUpdate(array(
      'email' => 'john.doe@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe'
    ));
    $this->subscriber_2 = Subscriber::createOrUpdate(array(
      'email' => 'jane.doe@mailpoet.com',
      'first_name' => 'Jane',
      'last_name' => 'Doe',
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
      'email' => 'john.doe@mailpoet.com',
      'first_name' => 'John',
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

    $subscriber = Subscriber::where('email', 'john.doe@mailpoet.com')->findOne();
    $subscriber_segments = $subscriber->segments()->findMany();
    expect($subscriber_segments)->count(2);
    expect($subscriber_segments[0]->name)->equals($this->segment_1->name);
    expect($subscriber_segments[1]->name)->equals($this->segment_2->name);

    $response = $router->save(/* missing data */);
    expect($response['result'])->false();
    expect($response['errors'][0])->equals('Please enter your email address.');

    $invalid_data = array(
      'email' => 'john.doe@invalid',
      'first_name' => 'John',
      'last_name' => 'Doe'
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
    expect($updated_subscriber->email)->equals('jane.doe@mailpoet.com');
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

  function _after() {
    Segment::deleteMany();
    Subscriber::deleteMany();
  }
}
