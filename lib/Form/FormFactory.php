<?php

namespace MailPoet\Form;

use MailPoet\Entities\FormEntity;
use MailPoet\Form\Templates\TemplateRepository;
use MailPoet\Settings\SettingsController;

class FormFactory {

  /** @var FormsRepository */
  private $formRepository;

  /** @var TemplateRepository */
  private $formTemplateRepository;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    FormsRepository $formRepository,
    TemplateRepository $formTemplateRepository,
    SettingsController $settings
  ) {
    $this->formRepository = $formRepository;
    $this->formTemplateRepository = $formTemplateRepository;
    $this->settings = $settings;
  }

  public function createFormFromTemplate(string $templateId, array $settings = []): FormEntity {
    if (!isset($settings['success_message'])) {
      $settings['success_message'] = $this->getDefaultSuccessMessage();
    }
    $formEntity = $this->formTemplateRepository->getFormEntityForTemplate($templateId);
    $formSettings = $formEntity->getSettings() ?? [];
    $formEntity->setSettings(array_merge($formSettings, $settings));
    $this->formRepository->persist($formEntity);
    $this->formRepository->flush();
    return $formEntity;
  }

  public function createEmptyForm(): FormEntity {
    return $this->createFormFromTemplate(TemplateRepository::INITIAL_FORM_TEMPLATE);
  }

  /**
   * @param int $defaultSegmentId
   * @return FormEntity|null
   */
  public function ensureDefaultFormExists(int $defaultSegmentId) {
    if ($this->formRepository->count()) {
      return null;
    }
    return $this->createFormFromTemplate(
      TemplateRepository::DEFAULT_FORM_TEMPLATE,
      ['segments' => [(string)$defaultSegmentId]]
    );
  }

  private function getDefaultSuccessMessage() {
    if ($this->settings->get('signup_confirmation.enabled')) {
      return __('Check your inbox or spam folder to confirm your subscription.', 'mailpoet');
    }
    return __('You’ve been successfully subscribed to our newsletter!', 'mailpoet');
  }
}
