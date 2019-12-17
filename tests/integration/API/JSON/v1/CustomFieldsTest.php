<?php

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder;
use MailPoet\API\JSON\v1\CustomFields;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\CustomField;

class CustomFieldsTest extends \MailPoetTest {

  /** @var CustomFieldsRepository */
  private $repository;

  private $custom_fields = [
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

  function _before() {
    parent::_before();
    $this->repository = ContainerWrapper::getInstance(WP_DEBUG)->get(CustomFieldsRepository::class);
    $this->repository->truncate();
    foreach ($this->custom_fields as $custom_field) {
      $this->repository->createOrUpdate($custom_field);
    }
  }

  function testItCanGetAllCustomFields() {
    $router = new CustomFields($this->repository, new CustomFieldsResponseBuilder());
    $response = $router->getAll();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->count(count($this->custom_fields));

    foreach ($response->data as $custom_field) {
      expect($custom_field['name'])->notEmpty();
      expect($custom_field['type'])->notEmpty();
      expect($custom_field['params'])->notEmpty();
    }
  }

  function testItCanDeleteACustomField() {
    $custom_field = CustomField::where('type', 'date')->findOne();
    $custom_field_id = $custom_field->id();

    $router = new CustomFields($this->repository, new CustomFieldsResponseBuilder());
    $response = $router->delete(['id' => $custom_field_id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $custom_field = CustomField::where('type', 'date')->findOne();
    expect($custom_field)->false();

    $response = $router->delete(['id' => $custom_field_id]);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  function testItCanSaveACustomField() {
    $new_custom_field = [
      'name' => 'New custom field',
      'type' => 'text',
      'params' => [],
    ];

    $router = new CustomFields($this->repository, new CustomFieldsResponseBuilder());
    $response = $router->save($new_custom_field);
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

  function testItCanGetACustomField() {
    $custom_field = $this->repository->findOneBy(['name' => 'CF: text']);

    $router = new CustomFields($this->repository, new CustomFieldsResponseBuilder());
    $response = $router->get(['id' => $custom_field->getId()]);

    expect($response->data['name'])->equals('CF: text');
    expect($response->data['type'])->equals('text');
    expect($response->data['params'])->notEmpty();

    $response = $router->get(['id' => 'not_an_id']);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }
}
