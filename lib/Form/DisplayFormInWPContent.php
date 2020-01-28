<?php

namespace MailPoet\Form;

use MailPoet\Entities\FormEntity;
use MailPoet\WP\Functions as WPFunctions;

class DisplayFormInWPContent {

  const NO_FORM_TRANSIENT_KEY = 'no_forms_displayed_bellow_content';

  /** @var WPFunctions */
  private $wp;

  /** @var FormsRepository */
  private $formsRepository;

  /** @var bool */
  private $appendedForm = false;

  public function __construct(WPFunctions $wp, FormsRepository $formsRepository) {
    $this->wp = $wp;
    $this->formsRepository = $formsRepository;
  }

  public function display(string $content): string {
    $this->appendedForm = false;
    $result = $content;
    if (!$this->wp->isSingle()) return $result;
    if ($this->wp->getTransient(DisplayFormInWPContent::NO_FORM_TRANSIENT_KEY)) return $result;
    $forms = $this->formsRepository->findAll();
    foreach ($forms as $form) {
      $result .= $this->getContentBellow($form);
    }
    if (!$this->appendedForm) {
      $this->wp->setTransient(DisplayFormInWPContent::NO_FORM_TRANSIENT_KEY, true);
    }
    return $result;
  }

  private function getContentBellow(FormEntity $form): string {
    if (!$this->shouldDisplayFormBellowContent($form)) return '';
    $this->appendedForm = true;
    return Renderer::render([
      'body' => $form->getBody(),
      'styles' => $form->getStyles(),
    ]);
  }

  private function shouldDisplayFormBellowContent(FormEntity $form): bool {
    $settings = $form->getSettings();
    if (!is_array($settings)) return false;
    if (!isset($settings['placeFormBellowAllPosts'])) return false;
    if (
      ($settings['placeFormBellowAllPosts'] === '1')
      && !$this->wp->isPage()
    ) return true;
    if (
      ($settings['placeFormBellowAllPages'] === '1')
      && $this->wp->isPage()
    ) return true;
    return false;
  }

}
