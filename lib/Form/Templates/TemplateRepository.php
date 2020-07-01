<?php

namespace MailPoet\Form\Templates;

use MailPoet\Entities\FormEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\UnexpectedValueException;

class TemplateRepository {
  const INITIAL_FORM_TEMPLATE = 'initial_form';

  private $templates = [
    'initial_form' => InitialForm::class,
    'demo_form' => DemoForm::class,
  ];

  /** @var SettingsController */
  private $settings;

  public function __construct(SettingsController $settings) {
    $this->settings = $settings;
  }

  public function getFormEntityForTemplate($templateId): FormEntity {
    if (!isset($this->templates[$templateId])) {
      throw UnexpectedValueException::create()
        ->withErrors(["Template with id $templateId doesn't exist."]);
    }
    /** @var Template $template */
    $template = new $this->templates[$templateId]();
    $formEntity = new FormEntity($template->getName());
    $formEntity->setBody($template->getBody());
    $settings = $formEntity->getSettings();
    $settings['success_message'] = $this->getDefaultSuccessMessage();
    $formEntity->setSettings($settings);
    $formEntity->setStyles($template->getStyles());
    return $formEntity;
  }

  private function getDefaultSuccessMessage() {
    if ($this->settings->get('signup_confirmation.enabled')) {
      return __('Check your inbox or spam folder to confirm your subscription.', 'mailpoet');
    }
    return __('You’ve been successfully subscribed to our newsletter!', 'mailpoet');
  }
}
