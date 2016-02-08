<?php

use \MailPoet\Router\Subscribers;
use \MailPoet\Models\Subscriber;
use \MailPoet\Models\Segment;

class SubscribersCest {
  function _before() {
    $this->subscriber_1 = Subscriber::createOrUpdate(array(
      'email' => 'john.doe@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe'
    ));
    $this->subscriber_2 = Subscriber::createOrUpdate(array(
      'email' => 'jane.doe@mailpoet.com',
      'first_name' => 'Jane',
      'last_name' => 'Doe'
    ));
    $this->segment_1 = Segment::createOrUpdate(array('name' => 'Segment 1'));
    $this->segment_2 = Segment::createOrUpdate(array('name' => 'Segment 2'));
  }

  function itCanGetASubscriber() {
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

  function itCanSaveANewSubscriber() {
    $valid_data = array(
      'email' => 'john.doe@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe'
    );

    $router = new Subscribers();
    $response = $router->save($valid_data);
    expect($response['result'])->true();
    expect($response)->hasntKey('errors');

    $response = $router->save(/* missing data */);
    expect($response['result'])->false();
    expect($response['errors'][0])->equals('You need to enter your email address.');

    $invalid_data = array(
      'email' => 'john.doe@invalid',
      'first_name' => 'John',
      'last_name' => 'Doe'
    );

    $response = $router->save($invalid_data);
    expect($response['result'])->false();
    expect($response['errors'][0])->equals('Your email address is invalid.');
  }

  function itCanSaveAnExistingSubscriber() {
    $router = new Subscribers();

    $subscriber_data = $this->subscriber_2->asArray();
    $subscriber_data['email'] = 'jane.doe@mailpoet.com';
    $subscriber_data['first_name'] = 'Super Jane';

    $response = $router->save($subscriber_data);
    expect($response['result'])->true();

    $updated_subscriber = Subscriber::findOne($this->subscriber_2->id());
    expect($updated_subscriber->email)->equals('jane.doe@mailpoet.com');
    expect($updated_subscriber->first_name)->equals('Super Jane');
  }

  function itCanRestoreASubscriber() {
    $this->subscriber_1->trash();

    expect($this->subscriber_1->deleted_at)->notNull();

    $router = new Subscribers();
    $router->restore($this->subscriber_1->id());

    $restored_subscriber = Subscriber::findOne($this->subscriber_1->id());
    expect($restored_subscriber->deleted_at)->null();
  }

  function itCanTrashASubscriber() {
    $router = new Subscribers();
    $response = $router->trash($this->subscriber_2->id());
    expect($response)->true();

    $trashed_subscriber = Subscriber::findOne($this->subscriber_2->id());
    expect($trashed_subscriber->deleted_at)->notNull();
  }

  function itCanDeleteASubscriber() {
    $router = new Subscribers();
    $response = $router->delete($this->subscriber_1->id());
    expect($response)->equals(1);

    expect(Subscriber::findOne($this->subscriber_1->id()))->false();
  }

  function itCanBulkDeleteSubscribers() {
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