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
  const INITIAL_FORM_TEMPLATE = 'initial_form';
  const DEFAULT_FORM_TEMPLATE = 'default_form';

  private $templates = [
    'initial_form' => InitialForm::class,
    'default_form' => DefaultForm::class,
    'template_3_below_pages' => Template3BelowPages::class,
    'template_3_fixed_bar' => Template3FixedBar::class,
    'template_3_popup' => Template3Popup::class,
    'template_3_slide_in' => Template3SlideIn::class,
    'template_3_widget' => Template3Widget::class,
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
