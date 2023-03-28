<?php declare(strict_types = 1);

namespace MailPoet\Form;

use Codeception\Util\Fixtures;
use MailPoet\Entities\FormEntity;

class FormSaveControllerTest extends \MailPoetTest {
  /** @var FormSaveController */
  private $saveController;

  public function _before() {
    parent::_before();
    $this->saveController = $this->diContainer->get(FormSaveController::class);
  }

  public function testItDuplicatesForms() {
    $form = $this->createForm();
    $duplicate = $this->saveController->duplicate($form);
    expect($duplicate->getName())->equals('Copy of ' . $form->getName());
    expect($duplicate->getDeletedAt())->equals(null);
    expect($duplicate->getBody())->equals($form->getBody());
    expect($duplicate->getStatus())->equals($form->getStatus());
  }

  private function createForm(): FormEntity {
    $form = new FormEntity('My Form');
    $form->setBody(Fixtures::get('form_body_template'));
    $this->entityManager->persist($form);
    $this->entityManager->flush();
    return $form;
  }
}
