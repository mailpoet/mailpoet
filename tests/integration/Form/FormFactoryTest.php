<?php

namespace MailPoet\Form;

use MailPoet\Entities\FormEntity;
use MailPoet\Form\Templates\TemplateRepository;

class FormFactoryTest extends \MailPoetTest {

  /** @var FormFactory */
  private $formFactory;

  public function _before() {
    parent::_before();
    $this->formFactory = $this->diContainer->get(FormFactory::class);
  }

  public function testItCreatesAndPersistEmptyForm() {
    $formEntity = $this->formFactory->createEmptyForm();
    expect($formEntity)->isInstanceOf(FormEntity::class);
    $this->entityManager->detach($formEntity);
    $formEntity = $this->entityManager->find(FormEntity::class, $formEntity->getId());
    assert($formEntity instanceof FormEntity);
    expect($formEntity->getName())->equals('');
    expect($formEntity->getBody())->notEmpty();
    expect($formEntity->getSettings())->notEmpty();
    expect($formEntity->getStyles())->string();
  }

  public function testItCreatesAndPersistFormFromTemplateId() {
    $formEntity = $this->formFactory->createFormFromTemplate(TemplateRepository::INITIAL_FORM_TEMPLATE);
    expect($formEntity)->isInstanceOf(FormEntity::class);
    $this->entityManager->detach($formEntity);
    $formEntity = $this->entityManager->find(FormEntity::class, $formEntity->getId());
    assert($formEntity instanceof FormEntity);
    expect($formEntity->getName())->equals('');
    expect($formEntity->getBody())->notEmpty();
    expect($formEntity->getSettings())->notEmpty();
    expect($formEntity->getStyles())->string();
  }
}
