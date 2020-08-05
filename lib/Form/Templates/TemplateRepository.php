<?php

namespace MailPoet\Form\Templates;

use MailPoet\Entities\FormEntity;
use MailPoet\Form\Templates\Templates\DefaultForm;
use MailPoet\Form\Templates\Templates\DemoForm;
use MailPoet\Form\Templates\Templates\InitialForm;
use MailPoet\UnexpectedValueException;

class TemplateRepository {
  const INITIAL_FORM_TEMPLATE = 'initial_form';
  const DEFAULT_FORM_TEMPLATE = 'default_form';

  private $templates = [
    'initial_form' => InitialForm::class,
    'default_form' => DefaultForm::class,
    'demo_form' => DemoForm::class,
  ];

  public function getFormEntityForTemplate(string $templateId): FormEntity {
    if (!isset($this->templates[$templateId])) {
      throw UnexpectedValueException::create()
        ->withErrors(["Template with id $templateId doesn't exist."]);
    }
    /** @var Template $template */
    $template = new $this->templates[$templateId]();
    $formEntity = new FormEntity($template->getName());
    $formEntity->setBody($template->getBody());
    $formEntity->setSettings($template->getSettings());
    $formEntity->setStyles($template->getStyles());
    return $formEntity;
  }

  /**
   * @param string[] $templateIds
   * @return FormEntity[] associative array with template ids as keys
   */
  public function getFormsForTemplates(array $templateIds): array {
    $result = [];
    foreach ($templateIds as $templateId) {
      $result[$templateId] = $this->getFormEntityForTemplate($templateId);
    }
    return $result;
  }
}
