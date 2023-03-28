<?php declare(strict_types = 1);

namespace MailPoet\Form;

use MailPoet\Entities\FormEntity;

class FormsRepositoryTest extends \MailPoetTest {
  /** @var FormsRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(FormsRepository::class);
  }

  public function testItCanDeleteForm() {
    $form = $this->createForm('Form 1');
    expect($this->repository->findOneById($form->getId()))->isInstanceOf(FormEntity::class);
    $this->repository->delete($form);
    expect($form->getId())->null();
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

  private function createForm(string $name): FormEntity {
    $form = new FormEntity($name);
    $this->repository->persist($form);
    $this->repository->flush();
    return $form;
  }
}
