<?php
use \MailPoet\Models\Subscriber;

class SubscriberCest {

    function _before() {
      $this->data = array(
        'first_name' => 'John',
        'last_name' => 'Mailer',
        'email' => 'john@mailpoet.com'
      );
      $subscriber = Subscriber::where('email', $this->data['email'])->findOne();
      if ($subscriber) {
        $this->subscriber = $subscriber;
        return;
      }

      $this->subscriber = Subscriber::create();

      $this
        ->subscriber
        ->first_name = $this->data['first_name'];

      $this
        ->subscriber
        ->last_name = $this->data['last_name'];

      $this->subscriber->email = $this->data['email'];

      $this->subscriber->save();
    }

    function itCanBeCreated() {
      $subscriber = Subscriber::where('email', $this->data['email'])->findOne();
      expect($subscriber->id)->notNull();
    }

    function itHasAFirstName() {
      $subscriber = Subscriber::where('email', $this->data['email'])->findOne();
      expect($subscriber->first_name)
        ->equals($this->data['first_name']);
    }

    function itHasALastName() {
      $subscriber = Subscriber::where('email', $this->data['email'])->findOne();
      expect($subscriber->last_name)
        ->equals($this->data['last_name']);
    }

    function itHasAnEmail() {
      $subscriber = Subscriber::where('email', $this->data['email'])->findOne();
      expect($subscriber->email)
        ->equals($this->data['email']);
    }

    function emailMustBeUnique() {
      $conflict_subscriber = Subscriber::create();
      $conflict_subscriber->first_name = 'First';
      $conflict_subscriber->last_name = 'Last';
      $conflict_subscriber->email = $this->data['email'];
      $conflicted = false;
      try {
        $conflict_subscriber->save();
      } catch (Exception $e) {
        $conflicted = true;
      }
      expect($conflicted)->equals(true);
    }

    function onUpdateWorks () {
      $to_update = Subscriber::where('email', 'hello@world')->findOne();
      if (empty($to_update)) {
        $created = Subscriber::create();
        $created->first_name = 'Hello';
        $created->last_name = 'World';
        $created->email = 'hello@world';
        $beforeCreate = time();
        $created->save();
        $to_update = Subscriber::where('email', 'hello@world')->findOne();
        expect(is_string($to_update->created_at))->equals(true);
        expect(strtotime($to_update->created_at) >= $beforeCreate)->equals(true);
      }
      $to_update->last_name = 'World!';
      $beforeUpdate = time();
      $to_update->save();
      $updated = Subscriber::where('email', 'hello@world')->findOne();
      expect(is_string($updated->updated_at))->equals(true);
      expect(strtotime($updated->updated_at) >= $beforeUpdate)->equals(true);
    }

    function _after() {
      $subscriber = Subscriber::where('email', $this->data['email'])->findOne()->delete();
    }

}
