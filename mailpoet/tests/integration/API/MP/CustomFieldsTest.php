<?php declare(strict_types = 1);

namespace MailPoet\Test\API\MP;

use MailPoet\API\MP\v1\API;
use MailPoet\API\MP\v1\APIException;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;

class CustomFieldsTest extends \MailPoetTest {
  /** @var API */
  private $api;

  /** @var  CustomFieldsRepository */
  private $customFieldRepository;

  public function _before(): void {
    parent::_before();
    $this->customFieldRepository = $this->diContainer->get(CustomFieldsRepository::class);
    $this->api = $this->diContainer->get(API::class);
  }

  public function testItReturnsDefaultSubscriberFields() {
    $response = $this->api->getSubscriberFields();

    expect($response)->contains([
      'id' => 'email',
      'name' => __('Email', 'mailpoet'),
      'type' => 'text',
      'params' => [
        'required' => '1',
      ],
    ]);
    expect($response)->contains([
      'id' => 'first_name',
      'name' => __('First name', 'mailpoet'),
      'type' => 'text',
      'params' => [
        'required' => '',
      ],
    ]);
    expect($response)->contains([
      'id' => 'last_name',
      'name' => __('Last name', 'mailpoet'),
      'type' => 'text',
      'params' => [
        'required' => '',
      ],
    ]);
  }

  public function testItReturnsCustomFields() {
    $customField1 = $this->customFieldRepository->createOrUpdate([
      'name' => 'text custom field',
      'type' => CustomFieldEntity::TYPE_TEXT,
      'params' => ['required' => '1', 'date_type' => 'year_month_day'],
    ]);
    $customField2 = $this->customFieldRepository->createOrUpdate([
      'name' => 'checkbox custom field',
      'type' => CustomFieldEntity::TYPE_CHECKBOX,
      'params' => ['required' => ''],
    ]);
    $response = $this->api->getSubscriberFields();
    expect($response)->contains([
      'id' => 'cf_' . $customField1->getId(),
      'name' => 'text custom field',
      'type' => 'text',
      'params' => [
        'required' => '1',
        'label' => 'text custom field',
        'date_type' => 'year_month_day',
      ],
    ]);
    expect($response)->contains([
      'id' => 'cf_' . $customField2->getId(),
      'name' => 'checkbox custom field',
      'type' => 'checkbox',
      'params' => [
        'required' => '',
        'label' => 'checkbox custom field',
      ],
    ]);
  }

  public function testItCreateNewCustomField() {
    $response = $this->api->addSubscriberField([
      'name' => 'text custom field',
      'type' => 'text',
      'params' => [
        'required' => '1',
        'label' => 'text custom field',
        'date_type' => 'year_month_day',
      ],
    ]);
    expect($response)->array();
    expect($response)->hasKey('id');
    expect($response)->hasKey('name');
    expect($response)->hasKey('type');
    expect($response)->hasKey('params');
    expect($response['params'])->array();
  }

  public function testItFailsToCreateNewCustomField() {
    $this->expectException(APIException::class);
    $this->api->addSubscriberField([
      'type' => 'text',
      'params' => [
        'required' => '1',
        'label' => 'text custom field',
        'date_type' => 'year_month_day',
      ],
    ]);
  }
}
