<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\CustomFields;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;

class CustomFieldsTest extends \MailPoetTest {

  /** @var CustomFieldsRepository */
  private $repository;

  /** @var CustomFields */
  private $endpoint;

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
    $this->repository = $this->diContainer->get(CustomFieldsRepository::class);
    foreach ($this->customFields as $customField) {
      $this->repository->createOrUpdate($customField);
    }
    $this->endpoint = $this->diContainer->get(CustomFields::class);
  }

  public function testItCanGetAllCustomFields() {
    $response = $this->endpoint->getAll();
    verify($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->count(count($this->customFields));

    foreach ($response->data as $customField) {
      verify($customField['name'])->notEmpty();
      verify($customField['type'])->notEmpty();
      verify($customField['params'])->notEmpty();
    }
  }

  public function testItCanDeleteACustomField() {
    $customField = $this->repository->findOneBy(['type' => 'date']);
    $this->assertInstanceOf(CustomFieldEntity::class, $customField);
    $customFieldId = $customField->getId();

    $response = $this->endpoint->delete(['id' => $customFieldId]);
    verify($response->status)->equals(APIResponse::STATUS_OK);

    $customField = $this->repository->findOneBy(['type' => 'date']);
    verify($customField)->null();

    $response = $this->endpoint->delete(['id' => $customFieldId]);
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  public function testItCanSaveACustomField() {
    $newCustomField = [
      'name' => 'New custom field',
      'type' => 'text',
      'params' => [],
    ];

    $response = $this->endpoint->save($newCustomField);
    verify($response->status)->equals(APIResponse::STATUS_OK);

    // missing type
    $response = $this->endpoint->save(['name' => 'New custom field1']);
    verify($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    // missing name
    $response = $this->endpoint->save(['type' => 'text']);
    verify($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    // missing data
    $response = $this->endpoint->save();
    verify($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
  }

  public function testItSanitizesCheckboxValueButKeepsAllowedHTML() {
    $newCustomField = [
      'name' => 'New custom field',
      'type' => 'checkbox',
      'params' => [
        'values' => [
          [
            'label' => 'label',
            'value' => '"><img src=e onerror=alert(1) <strong>hello</strong><a href="https://example.com">link</a>',
          ],
        ],
      ],
    ];

    $response = $this->endpoint->save($newCustomField);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['params']['values'][0]['value'])
      ->equals('"&gt;&lt;img src=e onerror=alert(1) <strong>hello</strong><a href="https://example.com">link</a>');
  }

  public function testItCanGetACustomField() {
    $customField = $this->repository->findOneBy(['name' => 'CF: text']);
    $this->assertInstanceOf(CustomFieldEntity::class, $customField);


    $response = $this->endpoint->get(['id' => $customField->getId()]);

    verify($response->data['name'])->equals('CF: text');
    verify($response->data['type'])->equals('text');
    verify($response->data['params'])->notEmpty();

    $response = $this->endpoint->get(['id' => 'not_an_id']);
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }
}
