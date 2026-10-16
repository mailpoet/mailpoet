<?php declare(strict_types = 1);

namespace MailPoet\Subscription;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;

class RegistrationTest extends \MailPoetTest {
  private Registration $registration;
  private SubscribersRepository $subscribersRepository;
  private SettingsController $settings;

  public function _before() {
    $this->registration = $this->diContainer->get(Registration::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
  }

  public function testItRendersTheSavedLabel() {
    $this->settings->set('subscribe.on_register.label', 'Join our list');
    verify($this->renderForm())->stringContainsString('Join our list');
  }

  public function testItRendersTheDefaultLabelWhenTheSavedLabelIsEmpty() {
    $this->settings->set('subscribe.on_register.label', '');
    verify($this->renderForm())->stringContainsString('Yes, please add me to your mailing list.');
  }

  public function testItRendersTheDefaultLabelWhenTheSavedLabelIsWhitespace() {
    $this->settings->set('subscribe.on_register.label', '   ');
    verify($this->renderForm())->stringContainsString('Yes, please add me to your mailing list.');
  }

  public function testItAddsSubscriber() {
    $_POST['mailpoet'] = ['subscribe_on_register' => true];
    $this->registration->onRegister(new \WP_Error(), 'login', 'tester@email.com');
    $subscriber = $this->subscribersRepository->findOneBy(['email' => 'tester@email.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
  }

  public function testItDoesntAddSubscriberWhenCheckboxIsNotChecked() {
    $_POST['mailpoet'] = ['subscribe_on_register' => false];
    $this->registration->onRegister(new \WP_Error(), 'login', 'tester@email.com');
    $subscriber = $this->subscribersRepository->findOneBy(['email' => 'tester@email.com']);
    verify($subscriber)->null();
  }

  public function testItDoesntAddSubscriberWhenEmailIsEmpty() {
    $_POST['mailpoet'] = ['subscribe_on_register' => true];
    $initialCount = count($this->subscribersRepository->findAll());
    $this->registration->onRegister(new \WP_Error(), 'login', '');
    verify($initialCount)->equals(count($this->subscribersRepository->findAll()));
  }

  public function testItAddsSubscriberOnMultisite() {
    $_POST['mailpoet'] = ['subscribe_on_register' => true];
    $result = [
      'errors' => new \WP_Error(),
      'user_name' => 'login',
      'user_email' => 'tester@email.com',
    ];
    $this->registration->onMultiSiteRegister($result);
    $subscriber = $this->subscribersRepository->findOneBy(['email' => 'tester@email.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
  }

  public function testItDoesntAddSubscriberWithEmptyOnMultisite() {
    $_POST['mailpoet'] = ['subscribe_on_register' => true];
    $result = [
      'errors' => new \WP_Error(),
      'user_name' => 'login',
      'user_email' => '',
    ];
    $initialCount = count($this->subscribersRepository->findAll());
    $this->registration->onMultiSiteRegister($result);
    verify($initialCount)->equals(count($this->subscribersRepository->findAll()));
  }

  private function renderForm(): string {
    ob_start();
    $this->registration->extendForm();
    return (string)ob_get_clean();
  }
}
