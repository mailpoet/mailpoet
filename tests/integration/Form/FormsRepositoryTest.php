<?php

namespace MailPoet\Form;

use MailPoet\Entities\FormEntity;

class FormsRepositoryTest extends \MailPoetTest {
  /** @var FormsRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(FormsRepository::class);
  }

  public function testItCanTrashForm() {
    $form = $this->createForm('Form 1');
    expect($form->getDeletedAt())->null();
    $this->repository->trash($form);
    expect($form->getDeletedAt())->notNull();
  }

  public function testItCanRestoreForm() {
    $form = $this->createForm('Form 1');
    $this->repository->trash($form);
    expect($form->getDeletedAt())->notNull();
    $this->repository->restore($form);
    expect($form->getDeletedAt())->null();
  }

  public function _after() {
    $this->truncateEntity(FormEntity::class);
  }

  private function createForm(string $name): FormEntity {
    $form = new FormEntity($name);
    $this->repository->persist($form);
    $this->repository->flush();
    return $form;
  }
}
