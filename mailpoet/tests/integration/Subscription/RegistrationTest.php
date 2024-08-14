<?php declare(strict_types = 1);

namespace MailPoet\Subscription;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;

class RegistrationTest extends \MailPoetTest {
  private Registration $registration;
  private SubscribersRepository $subscribersRepository;

  public function _before() {
    $this->registration = $this->diContainer->get(Registration::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
  }

  public function testItAddsSubscriber() {
    $_POST['mailpoet']['subscribe_on_register'] = true;
    $this->registration->onRegister(new \WP_Error(), 'login', 'tester@email.com');
    $subscriber = $this->subscribersRepository->findOneBy(['email' => 'tester@email.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
  }

  public function testItDoesntAddSubscriberWhenCheckboxIsNotChecked() {
    $_POST['mailpoet']['subscribe_on_register'] = false;
    $this->registration->onRegister(new \WP_Error(), 'login', 'tester@email.com');
    $subscriber = $this->subscribersRepository->findOneBy(['email' => 'tester@email.com']);
    verify($subscriber)->null();
  }

  public function testItDoesntAddSubscriberWhenEmailIsEmpty() {
    $_POST['mailpoet']['subscribe_on_register'] = true;
    $initialCount = count($this->subscribersRepository->findAll());
    $this->registration->onRegister(new \WP_Error(), 'login', '');
    verify($initialCount)->equals(count($this->subscribersRepository->findAll()));
  }
}
