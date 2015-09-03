<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
*/
class UnitTester extends \Codeception\Actor {
  use _generated\UnitTesterActions;

  // generate random users
  function generateSubscribers($count, $data = array()) {
    for($i = 0; $i < $count; $i++) {
      $this->generateSubscriber($data);
    }
  }

  function generateSubscriber($data = array()) {
    $subscriber_data = array(
      'email' => sprintf('user%s@mailpoet.com', uniqid()),
      'first_name' => $this->generateName(),
      'last_name' => $this->generateName()
    );

    $subscriber = \MailPoet\Models\Subscriber::create();
    $subscriber->hydrate(array_merge($subscriber_data, $data));
    $subscriber->save();
  }

  protected function generateName() {
    $name = '';
    $length = mt_rand(6, 12);

    $vowels = 'aeiouy';
    $consonants = 'bcdfgjklmnpqrstvwxz';
    $specials = ' \'';
    $alphabet = $consonants.$vowels;
    $charset = $specials.$alphabet;

    // pick first letter in alphabet
    $name .= $alphabet{mt_rand(0, strlen($alphabet) - 1)};

    for($i = 0; $i < $length; $i++) {
      $name .= $charset{mt_rand(0, strlen($charset) - 1)};
    }

    // pick last letter in alphabet
    $name .= $alphabet{mt_rand(0, strlen($alphabet) - 1)};

    return ucfirst($name);
  }
}
