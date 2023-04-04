<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder;
use MailPoet\API\JSON\v1\CustomFields;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\CustomFieldEntity;

class CustomFieldsTest extends \MailPoetTest {

  /** @var CustomFieldsRepository */
  private $repository;

  private $customFields = [
    [
      'name' => 'CF: text',
      'type' => 'text',
      'params' => [
        'required' => '1',
        'validate' => '',
        'label' => 'CF: text',
      ],
    ],
    [
      'name' => 'CF: textarea',
      'type' => 'textarea',
      'params' => [
        'required' => '1',
        'validate' => '',
        'label' => 'CF: text area',
      ],
    ],
    [
      'name' => 'CF: radio',
      'type' => 'radio',
      'params' => [
        'values' =>
        [
          ['value' => 'one'],
          ['value' => 'two'],
          ['value' => 'three'],
        ],
        'required' => '1',
        'label' => 'CF: radio',
      ],
    ],
    [
      'name' => 'CF: date',
      'type' => 'date',
      'params' => [
        'required' => '1',
        'date_type' => 'year_month_day',
        'date_format' => '',
        'label' => 'CF: date',
      ],
    ],
  ];

  public function _before() {
    parent::_before();
    $this->repository = ContainerWrapper::getInstance(WP_DEBUG)->get(CustomFieldsRepository::class);
    foreach ($this->customFields as $customField) {
      $this->repository->createOrUpdate($customField);
    }
  }

  public function testItCanGetAllCustomFields() {
    $router = new CustomFields($this->repository, new CustomFieldsResponseBuilder());
    $response = $router->getAll();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->count(count($this->customFields));

    foreach ($response->data as $customField) {
      expect($customField['name'])->notEmpty();
      expect($customField['type'])->notEmpty();
      expect($customField['params'])->notEmpty();
    }
  }

  public function testItCanDeleteACustomField() {
    $customField = $this->repository->findOneBy(['type' => 'date']);
    $this->assertInstanceOf(CustomFieldEntity::class, $customField);
    $customFieldId = $customField->getId();

    $router = new CustomFields($this->repository, new CustomFieldsResponseBuilder());
    $response = $router->delete(['id' => $customFieldId]);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $customField = $this->repository->findOneBy(['type' => 'date']);
    expect($customField)->null();

    $response = $router->delete(['id' => $customFieldId]);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  public function testItCanSaveACustomField() {
    $newCustomField = [
      'name' => 'New custom field',
      'type' => 'text',
      'params' => [],
    ];

    $router = new CustomFields($this->repository, new CustomFieldsResponseBuilder());
    $response = $router->save($newCustomField);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    // missing type
    $response = $router->save(['name' => 'New custom field1']);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    // missing name
    $response = $router->save(['type' => 'text']);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    // missing data
    $response = $router->save();
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
  }

  public function testItCanGetACustomField() {
    $customField = $this->repository->findOneBy(['name' => 'CF: text']);
    $this->assertInstanceOf(CustomFieldEntity::class, $customField);

    $router = new CustomFields($this->repository, new CustomFieldsResponseBuilder());
    $response = $router->get(['id' => $customField->getId()]);

    expect($response->data['name'])->equals('CF: text');
    expect($response->data['type'])->equals('text');
    expect($response->data['params'])->notEmpty();

    $response = $router->get(['id' => 'not_an_id']);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }
}
