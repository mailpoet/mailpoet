<?php

namespace MailPoet\Form\Templates;

use MailPoet\Entities\FormEntity;
use MailPoet\UnexpectedValueException;

class TemplateRepository {
  const INITIAL_FORM_TEMPLATE = 'initial_form';

  private $templates = [
    'initial_form' => InitialForm::class,
    'demo_form' => DemoForm::class,
  ];

  public function getFormEntityForTemplate($templateId): FormEntity {
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
}
