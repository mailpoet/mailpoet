<?php

namespace MailPoet\Form\Templates;

use MailPoet\Form\Templates\Templates\DefaultForm;
use MailPoet\Form\Templates\Templates\InitialForm;
use MailPoet\Form\Templates\Templates\Template3BelowPages;
use MailPoet\Form\Templates\Templates\Template3FixedBar;
use MailPoet\Form\Templates\Templates\Template3Popup;
use MailPoet\Form\Templates\Templates\Template3SlideIn;
use MailPoet\Form\Templates\Templates\Template3Widget;
use MailPoet\UnexpectedValueException;

class TemplateRepository {
  const INITIAL_FORM_TEMPLATE = InitialForm::ID;
  const DEFAULT_FORM_TEMPLATE = DefaultForm::ID;

  private $templates = [
    InitialForm::ID => InitialForm::class,
    DefaultForm::ID => DefaultForm::class,
    Template3BelowPages::ID => Template3BelowPages::class,
    Template3FixedBar::ID => Template3FixedBar::class,
    Template3Popup::ID => Template3Popup::class,
    Template3SlideIn::ID => Template3SlideIn::class,
    Template3Widget::ID => Template3Widget::class,
  ];

  public function getFormTemplate(string $templateId): FormTemplate {
    if (!isset($this->templates[$templateId])) {
      throw UnexpectedValueException::create()
        ->withErrors(["Template with id $templateId doesn't exist."]);
    }
    /** @var FormTemplate $template */
    $template = new $this->templates[$templateId]();
    return $template;
  }

  /**
   * @param string[] $templateIds
   * @return FormTemplate[] associative array with template ids as keys
   */
  public function getFormTemplates(array $templateIds): array {
    $result = [];
    foreach ($templateIds as $templateId) {
      $result[$templateId] = $this->getFormTemplate($templateId);
    }
    return $result;
  }
}
