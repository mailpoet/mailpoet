<?php

namespace MailPoet\Test\Form\Templates;

use MailPoet\Form\Templates\Template;
use MailPoet\Form\Templates\TemplateRepository;

class TemplatesRepositoryTest extends \MailPoetUnitTest {
  /** @var TemplateRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->repository = new TemplateRepository();
  }

  public function testItCanBuildFormTemplate() {
    $formEntity = $this->repository->getFormTemplate(TemplateRepository::INITIAL_FORM_TEMPLATE);
    expect($formEntity)->isInstanceOf(Template::class);
    expect($formEntity->getStyles())->notEmpty();
    expect($formEntity->getBody())->notEmpty();
    expect($formEntity->getSettings())->notEmpty();
  }
}
