<?php

namespace MailPoet\Form;

use MailPoet\Entities\FormEntity;
use MailPoet\Form\Templates\TemplateRepository;

class FormFactory {

  /** @var FormsRepository */
  private $formRepository;

  /** @var TemplateRepository */
  private $formTemplateRepository;

  public function __construct(
    FormsRepository $formRepository,
    TemplateRepository $formTemplateRepository
  ) {
    $this->formRepository = $formRepository;
    $this->formTemplateRepository = $formTemplateRepository;
  }

  public function createFormFromTemplate(string $templateId, array $settings = []): FormEntity {
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
}
