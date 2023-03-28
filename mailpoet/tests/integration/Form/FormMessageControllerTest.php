<?php declare(strict_types = 1);

namespace MailPoet\Form;

use MailPoet\Entities\FormEntity;
use MailPoet\Settings\SettingsController;

class FormMessageControllerTest extends \MailPoetTest {
  /** @var FormMessageController */
  private $controller;

  /** @var SettingsController */
  private $settings;

  /** @var FormsRepository */
  private $formsRepository;

  public function _before() {
    parent::_before();
    $this->controller = $this->diContainer->get(FormMessageController::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->formsRepository = $this->diContainer->get(FormsRepository::class);
  }

  public function testItReturnsCorrectSuccessMessage(): void {
    $this->settings->set('signup_confirmation.enabled', 1);
    expect($this->controller->getDefaultSuccessMessage())->equals(__('Check your inbox or spam folder to confirm your subscription.', 'mailpoet'));
    $this->settings->set('signup_confirmation.enabled', 0);
    expect($this->controller->getDefaultSuccessMessage())->equals(__('You’ve been successfully subscribed to our newsletter!', 'mailpoet'));
  }

  public function testItUpdatesSuccessMessagesForForms(): void {
    $this->settings->set('signup_confirmation.enabled', 1);
    $form = new FormEntity('test form');
    $form->setSettings(['success_message' => __('Check your inbox or spam folder to confirm your subscription.', 'mailpoet')]);
    $this->entityManager->persist($form);
    $this->entityManager->flush();

    $this->settings->set('signup_confirmation.enabled', 0);
    $this->controller->updateSuccessMessages();
    $forms = $this->formsRepository->findAll();
    expect($forms)->count(1);
    foreach ($forms as $form) {
      expect($form->getSettings()['success_message'] ?? null)->equals(__('You’ve been successfully subscribed to our newsletter!', 'mailpoet'));
    }

    $this->settings->set('signup_confirmation.enabled', 1);
    $this->controller->updateSuccessMessages();
    $forms = $this->formsRepository->findAll();
    expect($forms)->count(1);
    foreach ($forms as $form) {
      expect($form->getSettings()['success_message'] ?? null)->equals(__('Check your inbox or spam folder to confirm your subscription.', 'mailpoet'));
    }
  }
}
