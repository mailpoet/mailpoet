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
    verify($this->repository->findOneById($form->getId()))->instanceOf(FormEntity::class);
    $this->repository->delete($form);
    verify($form->getId())->null();
  }

  public function testItCanTrashForm() {
    $form = $this->createForm('Form 1');
    verify($form->getDeletedAt())->null();
    $this->repository->trash($form);
    verify($form->getDeletedAt())->notNull();
  }

  public function testItCanRestoreForm() {
    $form = $this->createForm('Form 1');
    $this->repository->trash($form);
    verify($form->getDeletedAt())->notNull();
    $this->repository->restore($form);
    verify($form->getDeletedAt())->null();
  }

  public function testItCountsFieldsInsideColumnsWithoutWarnings() {
    $form = new FormEntity('Form with columns');
    $form->setSettings([]);
    $form->setBody([
      [
        'type' => 'columns',
        'body' => [
          [
            'type' => 'column',
            'body' => [
              ['type' => 'text', 'id' => 'first_name', 'name' => 'First name'],
              ['type' => 'text', 'id' => '3', 'name' => 'Company'],
            ],
          ],
          [
            'type' => 'column',
            'body' => [
              ['type' => 'text', 'id' => 'email', 'name' => 'Email'],
              ['type' => 'submit', 'id' => 'submit', 'name' => 'Submit'],
            ],
          ],
        ],
      ],
    ]);
    $this->repository->persist($form);
    $this->repository->flush();

    $counts = $this->repository->getActiveFormsCountByType();

    verify($counts['with_first_name'])->equals(1);
    verify($counts['with_last_name'])->equals(0);
    verify($counts['with_custom_fields'])->equals(1);
    verify($counts['max_custom_fields'])->equals(1);
  }

  private function createForm(string $name): FormEntity {
    $form = new FormEntity($name);
    $this->repository->persist($form);
    $this->repository->flush();
    return $form;
  }
}
