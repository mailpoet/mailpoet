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

  public function testItCanOverrideTemplateSettings() {
    $settings = [
      'success_message' => 'Hello Buddy!',
      'segments' => [1, 2, 3],
    ];
    $formEntity = $this->formFactory->createFormFromTemplate(TemplateRepository::INITIAL_FORM_TEMPLATE, $settings);
    assert($formEntity instanceof FormEntity);
    $formSettings = $formEntity->getSettings() ?? [];
    expect($formSettings['success_message'])->equals('Hello Buddy!');
    expect($formSettings['segments'])->equals([1, 2, 3]);
  }

  public function testItCanEnsureDefaultFormExists() {
    $this->cleanup();
    $formEntity = $this->formFactory->ensureDefaultFormExists(2);
    assert($formEntity instanceof FormEntity);
    $formSettings = $formEntity->getSettings() ?? [];
    expect($formSettings['segments'])->equals(['2']);
    // Doesn't create any form if some exists
    $formEntity = $this->formFactory->ensureDefaultFormExists(2);
    expect($formEntity)->null();
  }

  public function _after() {
    parent::_after();
    $this->cleanup();
  }

  private function cleanup() {
    $this->truncateEntity(FormEntity::class);
  }
}
