<?php

namespace MailPoet\Test\Form\Templates;

use MailPoet\Entities\FormEntity;
use MailPoet\Form\Templates\TemplateRepository;

class TemplatesRepositoryTest extends \MailPoetUnitTest {
  /** @var TemplateRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->repository = new TemplateRepository();
  }

  public function testItCanBuildFormEntity() {
    $formEntity = $this->repository->getFormEntityForTemplate(TemplateRepository::INITIAL_FORM_TEMPLATE);
    expect($formEntity)->isInstanceOf(FormEntity::class);
    expect($formEntity->getStyles())->notEmpty();
    expect($formEntity->getBody())->notEmpty();
    expect($formEntity->getSettings())->notEmpty();
  }
}
