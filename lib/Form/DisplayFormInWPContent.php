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
      $this->saveNoPosts();
      return $content;
    }

    $result = $content;
    foreach ($forms as $form) {
      $result .= $this->getContentBellow($form);
    }

    return $result;
  }

  private function shouldDisplay(): bool {
    // this code ensures that we display the form only on a page which is related to single post
    if (!$this->wp->isSingle()) return false;
    $cache = $this->wp->getTransient(DisplayFormInWPContent::NO_FORM_TRANSIENT_KEY);
    if (isset($cache[$this->wp->getPostType()])) return false;
    return true;
  }

  private function saveNoPosts() {
    $stored = $this->wp->getTransient(DisplayFormInWPContent::NO_FORM_TRANSIENT_KEY);
    if (!is_array($stored)) $stored = [];
    $stored[$this->wp->getPostType()] = true;
    $this->wp->setTransient(DisplayFormInWPContent::NO_FORM_TRANSIENT_KEY, $stored);
  }

  /**
   * @return FormEntity[]
   */
  private function getForms(): array {
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
      && $this->wp->isSingular('post')
    ) return true;
    if (
      ($settings['placeFormBellowAllPages'] === '1')
      && $this->wp->isPage()
    ) return true;
    return false;
  }

}
