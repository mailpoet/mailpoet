<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\FormEntity;

class RequiredCustomFieldValidatorTest extends \MailPoetTest {
  /** @var CustomFieldEntity */
  private $customField;

  /** @var CustomFieldsRepository */
  private $customFieldRepository;

  /** @var RequiredCustomFieldValidator */
  private $validator;

  public function _before() {
    parent::_before();
    $this->customFieldRepository = $this->diContainer->get(CustomFieldsRepository::class);
    $this->validator = new RequiredCustomFieldValidator($this->customFieldRepository);
    $this->customField = $this->customFieldRepository->createOrUpdate([
      'name' => 'custom field',
      'type' => 'text',
      'params' => ['required' => '1'],
    ]);
  }

  public function testItValidatesDataWithoutCustomField() {
    $this->expectException('Exception');
    $this->validator->validate([]);
  }

  public function testItValidatesDataWithCustomFieldPassedAsId() {
    $this->validator->validate([$this->customField->getId() => 'value']);
  }

  public function testItValidatesDataWithCustomFieldPassedAsCFId() {
    $this->validator->validate(['cf_' . $this->customField->getId() => 'custom field']);
  }

  public function testItValidatesDataWithEmptyCustomField() {
    $this->expectException('Exception');
    $this->validator->validate([$this->customField->getId() => '']);
  }

  public function testItValidatesDataWithEmptyCustomFieldAsCFId() {
    $this->expectException('Exception');
    $this->validator->validate(['cf_' . $this->customField->getId() => '']);
  }

  public function testItValidatesOnlyFieldPresentInForm() {
    $this->customFieldRepository->createOrUpdate([
      'name' => 'custom field 2',
      'type' => 'text',
      'params' => ['required' => '1'],
    ]);
    $form = new FormEntity('form');
    $form->setBody([
      'type' => 'text',
      'name' => 'mandatory',
      'id' => $this->customField->getId(),
      'unique' => '1',
      'static' => '0',
      'params' => ['required' => '1'],
      'position' => '0',
    ]);
    $this->entityManager->persist($form);
    $this->entityManager->flush();
    $this->validator->validate(['cf_' . $this->customField->getId() => 'value'], $form);
  }
}
