<?php

namespace MailPoet\Form;

use MailPoet\Entities\FormEntity;
use MailPoet\WP\Functions as WPFunctions;

class DisplayFormInWPContent {

  /** @var WPFunctions */
  private $wp;

  /** @var FormsRepository */
  private $formsRepository;

  public function __construct(WPFunctions $wp, FormsRepository $formsRepository) {
    $this->wp = $wp;
    $this->formsRepository = $formsRepository;
  }

  // TODO remove transient in the api on form save

  public function display(string $content): string {
    $result = $content;
    if (!$this->wp->isSingle()) return $result;
    $forms = $this->formsRepository->findAll();
    foreach ($forms as $form) {
      $result .= $this->getContentBellow($form);
    }
    return $result;
  }

  private function getContentBellow(FormEntity $form): string {
    if (!$this->shouldDisplayFormBellowContent($form)) return '';
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
