<?php
use MailPoet\Models\Subscriber;
use MailPoet\Router\Subscribers;

class SubscribersCest {

  function _before() {
    $this->existingSubscriber = array(
      'first_name' => 'Marco',
      'last_name' => 'Lisci',
      'email' => 'marco@mailpoet.com'
    );
    $this->newSubscribers = array(
      array(
        'first_name' => 'Test',
        'last_name' => 'MailPoet',
        'email' => 'testmailpoet@gmail.com'
      ),
      array(
        'first_name' => 'Jonathan',
        'last_name' => 'Labreuille',
        'email' => 'jonathan@mailpoet.com'
      )
    );
    $this->subscribers = new Subscribers();
  }
  function itCanSetAnExistingSubscriber() {
    $marco = Subscriber::create();
    $marco->hydrate($this->existingSubscriber);
    $marco->save();
    $created = $this->subscribers->set(array(
      'first_name' => 'Marco',
      'last_name' => 'Lisci',
      'email' => 'marco@mailpoet.com'
    ));
    expect($created)->equals(FALSE);
  }
  function itCanSetNewSubscribers() {
    foreach($this->newSubscribers as $row) {
      $created = $this->subscribers->set($row);
      expect($created)->equals(TRUE);
    }
  }
  function itCanSelectAllSubscribers() {
    $marco = Subscriber::create();
    $marco->hydrate($this->existingSubscriber);
    $marco->save();
    foreach($this->newSubscribers as $row) {
      $this->subscribers->set($row);
    }
    $all = $this->subscribers->selectAll();
    expect(count($all))->equals(3);
  }
  function _after() {
    ORM::for_table(Subscriber::$_table)
      ->delete_many();
  }
}