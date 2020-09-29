<?php

namespace MailPoet\Form\Templates;

use MailPoet\Form\Templates\Templates\DefaultForm;
use MailPoet\Form\Templates\Templates\InitialForm;
use MailPoet\Form\Templates\Templates\Template10BelowPages;
use MailPoet\Form\Templates\Templates\Template10FixedBar;
use MailPoet\Form\Templates\Templates\Template10Popup;
use MailPoet\Form\Templates\Templates\Template10SlideIn;
use MailPoet\Form\Templates\Templates\Template10Widget;
use MailPoet\Form\Templates\Templates\Template1BelowPages;
use MailPoet\Form\Templates\Templates\Template1FixedBar;
use MailPoet\Form\Templates\Templates\Template1Popup;
use MailPoet\Form\Templates\Templates\Template1SlideIn;
use MailPoet\Form\Templates\Templates\Template1Widget;
use MailPoet\Form\Templates\Templates\Template3BelowPages;
use MailPoet\Form\Templates\Templates\Template3FixedBar;
use MailPoet\Form\Templates\Templates\Template3Popup;
use MailPoet\Form\Templates\Templates\Template3SlideIn;
use MailPoet\Form\Templates\Templates\Template3Widget;
use MailPoet\Form\Templates\Templates\Template4BelowPages;
use MailPoet\Form\Templates\Templates\Template4FixedBar;
use MailPoet\Form\Templates\Templates\Template4Popup;
use MailPoet\Form\Templates\Templates\Template4SlideIn;
use MailPoet\Form\Templates\Templates\Template4Widget;
use MailPoet\Form\Templates\Templates\Template6BelowPages;
use MailPoet\Form\Templates\Templates\Template6FixedBar;
use MailPoet\Form\Templates\Templates\Template6Popup;
use MailPoet\Form\Templates\Templates\Template6SlideIn;
use MailPoet\Form\Templates\Templates\Template6Widget;
use MailPoet\Form\Templates\Templates\Template7BelowPages;
use MailPoet\Form\Templates\Templates\Template7FixedBar;
use MailPoet\Form\Templates\Templates\Template7Popup;
use MailPoet\Form\Templates\Templates\Template7SlideIn;
use MailPoet\Form\Templates\Templates\Template7Widget;
use MailPoet\UnexpectedValueException;
use MailPoet\Util\CdnAssetUrl;
use MailPoet\WP\Functions as WPFunctions;

class TemplateRepository {
  const INITIAL_FORM_TEMPLATE = InitialForm::ID;
  const DEFAULT_FORM_TEMPLATE = DefaultForm::ID;

  /** @var CdnAssetUrl */
  private $cdnAssetUrl;

  /** @var WPFunctions */
  private $wp;

  private $templates = [
    InitialForm::ID => InitialForm::class,
    DefaultForm::ID => DefaultForm::class,
    Template1BelowPages::ID => Template1BelowPages::class,
    Template1FixedBar::ID => Template1FixedBar::class,
    Template1Popup::ID => Template1Popup::class,
    Template1SlideIn::ID => Template1SlideIn::class,
    Template1Widget::ID => Template1Widget::class,
    Template3BelowPages::ID => Template3BelowPages::class,
    Template3FixedBar::ID => Template3FixedBar::class,
    Template3Popup::ID => Template3Popup::class,
    Template3SlideIn::ID => Template3SlideIn::class,
    Template3Widget::ID => Template3Widget::class,
    Template4BelowPages::ID => Template4BelowPages::class,
    Template4FixedBar::ID => Template4FixedBar::class,
    Template4Popup::ID => Template4Popup::class,
    Template4SlideIn::ID => Template4SlideIn::class,
    Template4Widget::ID => Template4Widget::class,
    Template6BelowPages::ID => Template6BelowPages::class,
    Template6FixedBar::ID => Template6FixedBar::class,
    Template6Popup::ID => Template6Popup::class,
    Template6SlideIn::ID => Template6SlideIn::class,
    Template6Widget::ID => Template6Widget::class,
    Template7BelowPages::ID => Template7BelowPages::class,
    Template7FixedBar::ID => Template7FixedBar::class,
    Template7Popup::ID => Template7Popup::class,
    Template7SlideIn::ID => Template7SlideIn::class,
    Template7Widget::ID => Template7Widget::class,
    Template10BelowPages::ID => Template10BelowPages::class,
    Template10FixedBar::ID => Template10FixedBar::class,
    Template10Popup::ID => Template10Popup::class,
    Template10SlideIn::ID => Template10SlideIn::class,
    Template10Widget::ID => Template10Widget::class,
  ];

  public function __construct(CdnAssetUrl $cdnAssetUrl, WPFunctions $wp) {
    $this->cdnAssetUrl = $cdnAssetUrl;
    $this->wp = $wp;
  }

  public function getFormTemplate(string $templateId): FormTemplate {
    if (!isset($this->templates[$templateId])) {
      throw UnexpectedValueException::create()
        ->withErrors(["Template with id $templateId doesn't exist."]);
    }
    /** @var FormTemplate $template */
    $template = new $this->templates[$templateId]($this->cdnAssetUrl, $this->wp);
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
