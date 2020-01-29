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

  public function __construct(WPFunctions $wp, FormsRepository $formsRepository) {
    $this->wp = $wp;
    $this->formsRepository = $formsRepository;
  }

  public function display(string $content): string {
    if(!$this->shouldDisplay()) return $content;

    $forms = $this->getForms();
    if (count($forms) === 0) {
      $this->wp->setTransient(DisplayFormInWPContent::NO_FORM_TRANSIENT_KEY, true);
      return $content;
    }

    $result = $content;
    foreach ($forms as $form) {
      $result .= $this->getContentBellow($form);
    }

    return $result;
  }

  private function shouldDisplay():bool {
    if (!$this->wp->isSingle()) return false;
    if ($this->wp->getTransient(DisplayFormInWPContent::NO_FORM_TRANSIENT_KEY)) return false;
    return true;
  }

  /**
   * @return FormEntity[]
   */
  private function getForms():array {
    $forms = $this->formsRepository->findAll();
    return array_filter($forms, function($form) {
      return $this->shouldDisplayFormBellowContent($form);
    });
  }

  private function getContentBellow(FormEntity $form): string {
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
