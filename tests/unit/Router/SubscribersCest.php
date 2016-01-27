<?php

use \MailPoet\Router\Subscribers;
use \MailPoet\Models\Subscriber;
use \MailPoet\Models\Segment;

class SubscribersCest {
  function _before() {

  }

  function itCanGetASubscriber() {
    $subscriber = Subscriber::createOrUpdate(array(
      'email' => 'john.doe@mailpoet.com'
    ));
    expect($subscriber->id() > 0)->true();

    $router = new Subscribers();

    $response = $router->get(array('id' => $subscriber->id()));
    expect($response['id'])->equals($subscriber->id());

    $response = $router->get(array('id' => 'not_an_id'));
    expect($response)->false();

    $response = $router->get(/* missing argument */);
    expect($response)->false();
  }

  function itCanGetAllSubscribers(UnitTester $I) {
    $I->generateSubscribers(10);

    $router = new Subscribers();
    $result = $router->getAll();

    expect($result)->count(10);

    $model = Subscriber::create();
    foreach($result as $subscriber) {
      expect($subscriber['id'] > 0)->true();
      expect($subscriber['email'])->notEmpty();
    }
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
    $subscriber = Subscriber::createOrUpdate(array(
      'email' => 'john.doe@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe'
    ));
    expect($subscriber->id() > 0)->true();

    $router = new Subscribers();

    $subscriber_data = $subscriber->asArray();

    $subscriber_data['email'] = 'jane.doe@mailpoet.com';
    $subscriber_data['first_name'] = 'Jane';

    $response = $router->save($subscriber_data);
    expect($response['result'])->true();

    $updated_subscriber = Subscriber::findOne($subscriber->id());
    expect($updated_subscriber->email)->equals('jane.doe@mailpoet.com');
    expect($updated_subscriber->first_name)->equals('Jane');
  }

  function itCanSubscribeToSegments() {

  }

  function itCanRestoreASubscriber() {

  }

  function itCanTrashASubscriber() {

  }

  function itCanDeleteASubscriber() {

  }

  function _after() {
    ORM::forTable(Segment::$_table)->deleteMany();
    ORM::forTable(Subscriber::$_table)->deleteMany();
  }
}