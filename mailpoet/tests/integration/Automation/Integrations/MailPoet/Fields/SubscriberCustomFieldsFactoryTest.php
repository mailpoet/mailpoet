<?php declare(strict_types = 1);

namespace integration\Automation\Integrations\MailPoet\Fields;

use DateTimeImmutable;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\MailPoet\Fields\SubscriberCustomFieldsFactory;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SubscriberPayload;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetTest;

class SubscriberCustomFieldsFactoryTest extends MailPoetTest {
  public function testItHandlesTextCustomFields(): void {
    $cfText = $this->createCustomField('cf-text', CustomFieldEntity::TYPE_TEXT);
    $cfTextarea = $this->createCustomField('cf-textarea', CustomFieldEntity::TYPE_TEXTAREA);
    $cfNUmber = $this->createCustomField('cf-number', CustomFieldEntity::TYPE_TEXT, ['validate' => 'number']);
    $this->createCustomField('cf-empty', CustomFieldEntity::TYPE_TEXT);

    $subscriber = $this->createSubscriber();
    $this->addSubscriberField($subscriber, $cfText, 'text value');
    $this->addSubscriberField($subscriber, $cfTextarea, 'textarea value');
    $this->addSubscriberField($subscriber, $cfNUmber, '123');

    $subscriberPayload = new SubscriberPayload($subscriber);
    $fields = $this->getFieldsMap();

    // check basics
    $label = $cfText->getParams()['label'] ?? '';
    $this->assertCount(4, $fields);
    $this->assertSame('mailpoet:subscriber:custom-field:cf-text', $fields['cf-text']->getKey());
    $this->assertSame("Custom field: $label", $fields['cf-text']->getName());
    $this->assertSame([], $fields['cf-text']->getArgs());

    // check types
    $this->assertSame('string', $fields['cf-text']->getType());
    $this->assertSame('string', $fields['cf-textarea']->getType());
    $this->assertSame('number', $fields['cf-number']->getType());
    $this->assertSame('string', $fields['cf-empty']->getType());

    // check values
    $this->assertSame('text value', $fields['cf-text']->getValue($subscriberPayload));
    $this->assertSame('textarea value', $fields['cf-textarea']->getValue($subscriberPayload));
    $this->assertSame(123.0, $fields['cf-number']->getValue($subscriberPayload));
    $this->assertSame(null, $fields['cf-empty']->getValue($subscriberPayload)); // not set
  }

  public function testItHandlesCheckboxCustomFields(): void {
    $args = ['values' => ['value' => 'Checkbox value']];
    $cfCheckboxTrue = $this->createCustomField('cf-checkbox-true', CustomFieldEntity::TYPE_CHECKBOX, $args);
    $cfCheckboxFalse = $this->createCustomField('cf-checkbox-false', CustomFieldEntity::TYPE_CHECKBOX, $args);
    $this->createCustomField('cf-checkbox-empty', CustomFieldEntity::TYPE_CHECKBOX, $args);

    $subscriber = $this->createSubscriber();
    $this->addSubscriberField($subscriber, $cfCheckboxTrue, 'Checkbox value');
    $this->addSubscriberField($subscriber, $cfCheckboxFalse, '');

    $subscriberPayload = new SubscriberPayload($subscriber);
    $fields = $this->getFieldsMap();

    // check basics
    $label = $cfCheckboxTrue->getParams()['label'] ?? '';
    $this->assertCount(3, $fields);
    $this->assertSame('mailpoet:subscriber:custom-field:cf-checkbox-true', $fields['cf-checkbox-true']->getKey());
    $this->assertSame("Custom field: $label", $fields['cf-checkbox-true']->getName());
    $this->assertSame([], $fields['cf-checkbox-true']->getArgs());

    // check types
    $this->assertSame('boolean', $fields['cf-checkbox-true']->getType());
    $this->assertSame('boolean', $fields['cf-checkbox-false']->getType());
    $this->assertSame('boolean', $fields['cf-checkbox-empty']->getType());

    // check values
    $this->assertTrue($fields['cf-checkbox-true']->getValue($subscriberPayload));
    $this->assertFalse($fields['cf-checkbox-false']->getValue($subscriberPayload));
    $this->assertNull($fields['cf-checkbox-empty']->getValue($subscriberPayload)); // not set
  }

  public function testItHandlesRadioCustomFields(): void {
    $args = ['values' => [['value' => 'One'], ['value' => 'Two'], ['value' => 'Three']]];
    $cfRadio = $this->createCustomField('cf-radio', CustomFieldEntity::TYPE_RADIO, $args);
    $this->createCustomField('cf-radio-empty', CustomFieldEntity::TYPE_RADIO, $args);

    $subscriber = $this->createSubscriber();
    $this->addSubscriberField($subscriber, $cfRadio, 'Two');

    $subscriberPayload = new SubscriberPayload($subscriber);
    $fields = $this->getFieldsMap();

    // check basics
    $label = $cfRadio->getParams()['label'] ?? '';
    $this->assertCount(2, $fields);
    $this->assertSame('mailpoet:subscriber:custom-field:cf-radio', $fields['cf-radio']->getKey());
    $this->assertSame("Custom field: $label", $fields['cf-radio']->getName());
    $this->assertSame([
      'options' => [
        ['id' => 'One', 'name' => 'One'],
        ['id' => 'Two', 'name' => 'Two'],
        ['id' => 'Three', 'name' => 'Three'],
      ],
    ], $fields['cf-radio']->getArgs());

    // check types
    $this->assertSame('enum', $fields['cf-radio']->getType());
    $this->assertSame('enum', $fields['cf-radio-empty']->getType());

    // check values
    $this->assertSame('Two', $fields['cf-radio']->getValue($subscriberPayload));
    $this->assertSame(null, $fields['cf-radio-empty']->getValue($subscriberPayload)); // not set
  }

  public function testItHandlesSelectCustomFields(): void {
    $args = ['values' => [['value' => 'One'], ['value' => 'Two'], ['value' => 'Three']]];
    $cfSelect = $this->createCustomField('cf-select', CustomFieldEntity::TYPE_SELECT, $args);
    $this->createCustomField('cf-select-empty', CustomFieldEntity::TYPE_SELECT, $args);

    $subscriber = $this->createSubscriber();
    $this->addSubscriberField($subscriber, $cfSelect, 'Three');

    $subscriberPayload = new SubscriberPayload($subscriber);
    $fields = $this->getFieldsMap();

    // check basics
    $label = $cfSelect->getParams()['label'] ?? '';
    $this->assertCount(2, $fields);
    $this->assertSame('mailpoet:subscriber:custom-field:cf-select', $fields['cf-select']->getKey());
    $this->assertSame("Custom field: $label", $fields['cf-select']->getName());
    $this->assertSame([
      'options' => [
        ['id' => 'One', 'name' => 'One'],
        ['id' => 'Two', 'name' => 'Two'],
        ['id' => 'Three', 'name' => 'Three'],
      ],
    ], $fields['cf-select']->getArgs());

    // check types
    $this->assertSame('enum', $fields['cf-select']->getType());
    $this->assertSame('enum', $fields['cf-select-empty']->getType());

    // check values
    $this->assertSame('Three', $fields['cf-select']->getValue($subscriberPayload));
    $this->assertSame(null, $fields['cf-select-empty']->getValue($subscriberPayload)); // not set
  }

  public function testItHandlesYearMonthDayAndYearMonthCustomFields(): void {
    $ymdArgs = ['date_type' => 'year_month_day', 'date_format' => 'MM/DD/YYYY'];
    $cfYmd = $this->createCustomField('cf-ymd', CustomFieldEntity::TYPE_DATE, $ymdArgs);
    $cfYmdInvalid = $this->createCustomField('cf-ymd-invalid', CustomFieldEntity::TYPE_DATE, $ymdArgs);
    $this->createCustomField('cf-ymd-empty', CustomFieldEntity::TYPE_DATE, $ymdArgs);

    $ymArgs = ['date_type' => 'year_month', 'date_format' => 'MM/YYYY'];
    $cfYm = $this->createCustomField('cf-ym', CustomFieldEntity::TYPE_DATE, $ymArgs);
    $cfYmInvalid = $this->createCustomField('cf-ym-invalid', CustomFieldEntity::TYPE_DATE, $ymArgs);
    $this->createCustomField('cf-ym-empty', CustomFieldEntity::TYPE_DATE, $ymArgs);

    $subscriber = $this->createSubscriber();
    $this->addSubscriberField($subscriber, $cfYmd, '12/24/2022');
    $this->addSubscriberField($subscriber, $cfYmdInvalid, '2022-12-24'); // invalid format
    $this->addSubscriberField($subscriber, $cfYm, '12/2022');
    $this->addSubscriberField($subscriber, $cfYmInvalid, '12-2022'); // invalid format

    $subscriberPayload = new SubscriberPayload($subscriber);
    $fields = $this->getFieldsMap();

    // check basics
    $label = $cfYmd->getParams()['label'] ?? '';
    $this->assertCount(6, $fields);
    $this->assertSame('mailpoet:subscriber:custom-field:cf-ymd', $fields['cf-ymd']->getKey());
    $this->assertSame("Custom field: $label", $fields['cf-ymd']->getName());
    $this->assertSame([], $fields['cf-ymd']->getArgs());

    // check types
    foreach (range(0, 5) as $i) {
      $this->assertSame('datetime', array_values($fields)[$i]->getType());
    }

    // check values
    $timezone = $this->diContainer->get(WordPress::class)->wpTimezone();
    $this->assertEquals(new DateTimeImmutable('2022-12-24 00:00:00', $timezone), $fields['cf-ymd']->getValue($subscriberPayload));
    $this->assertSame(null, $fields['cf-ymd-invalid']->getValue($subscriberPayload)); // invalid
    $this->assertSame(null, $fields['cf-ymd-empty']->getValue($subscriberPayload)); // not set
    $this->assertEquals(new DateTimeImmutable('2022-12-01 00:00:00', $timezone), $fields['cf-ym']->getValue($subscriberPayload));
    $this->assertSame(null, $fields['cf-ym-invalid']->getValue($subscriberPayload)); // invalid
    $this->assertSame(null, $fields['cf-ym-empty']->getValue($subscriberPayload)); // not set
  }

  public function testItHandlesYearCustomFields(): void {
    $args = ['date_type' => 'year', 'date_format' => 'YYYY'];
    $cfYear = $this->createCustomField('cf-year', CustomFieldEntity::TYPE_DATE, $args);
    $cfYearInvalid = $this->createCustomField('cf-year-invalid', CustomFieldEntity::TYPE_DATE, $args);
    $this->createCustomField('cf-year-empty', CustomFieldEntity::TYPE_DATE, $args);

    $subscriber = $this->createSubscriber();
    $this->addSubscriberField($subscriber, $cfYear, '2022');
    $this->addSubscriberField($subscriber, $cfYearInvalid, '12-2022'); // invalid format

    $subscriberPayload = new SubscriberPayload($subscriber);
    $fields = $this->getFieldsMap();

    // check basics
    $label = $cfYear->getParams()['label'] ?? '';
    $this->assertCount(3, $fields);
    $this->assertSame('mailpoet:subscriber:custom-field:cf-year', $fields['cf-year']->getKey());
    $this->assertSame("Custom field: $label", $fields['cf-year']->getName());
    $this->assertSame([], $fields['cf-year']->getArgs());

    // check types
    $this->assertSame('integer', $fields['cf-year']->getType());
    $this->assertSame('integer', $fields['cf-year-invalid']->getType());
    $this->assertSame('integer', $fields['cf-year-empty']->getType());

    // check values
    $this->assertSame(2022, $fields['cf-year']->getValue($subscriberPayload));
    $this->assertSame(null, $fields['cf-year-invalid']->getValue($subscriberPayload)); // invalid
    $this->assertSame(null, $fields['cf-year-empty']->getValue($subscriberPayload)); // not set
  }

  public function testItHandlesMonthCustomFields(): void {
    $args = ['date_type' => 'month', 'date_format' => 'MM'];
    $cfMonth = $this->createCustomField('cf-month', CustomFieldEntity::TYPE_DATE, $args);
    $this->createCustomField('cf-month-empty', CustomFieldEntity::TYPE_DATE, $args);

    $subscriber = $this->createSubscriber();
    $this->addSubscriberField($subscriber, $cfMonth, '12');

    $subscriberPayload = new SubscriberPayload($subscriber);
    $fields = $this->getFieldsMap();

    // check basics
    $label = $cfMonth->getParams()['label'] ?? '';
    $this->assertCount(2, $fields);
    $this->assertSame('mailpoet:subscriber:custom-field:cf-month', $fields['cf-month']->getKey());
    $this->assertSame("Custom field: $label", $fields['cf-month']->getName());
    $this->assertSame([
      'options' => [
        ['id' => 1, 'name' => 'January'],
        ['id' => 2, 'name' => 'February'],
        ['id' => 3, 'name' => 'March'],
        ['id' => 4, 'name' => 'April'],
        ['id' => 5, 'name' => 'May'],
        ['id' => 6, 'name' => 'June'],
        ['id' => 7, 'name' => 'July'],
        ['id' => 8, 'name' => 'August'],
        ['id' => 9, 'name' => 'September'],
        ['id' => 10, 'name' => 'October'],
        ['id' => 11, 'name' => 'November'],
        ['id' => 12, 'name' => 'December'],
      ],
    ], $fields['cf-month']->getArgs());

    // check types
    $this->assertSame('enum', $fields['cf-month']->getType());
    $this->assertSame('enum', $fields['cf-month-empty']->getType());

    // check values
    $this->assertSame(12, $fields['cf-month']->getValue($subscriberPayload));
    $this->assertSame(null, $fields['cf-month-empty']->getValue($subscriberPayload)); // not set
  }

  public function testItHandlesDayCustomFields(): void {
    $args = ['date_type' => 'day', 'date_format' => 'DD'];
    $cfDay = $this->createCustomField('cf-day', CustomFieldEntity::TYPE_DATE, $args);
    $this->createCustomField('cf-day-empty', CustomFieldEntity::TYPE_DATE, $args);

    $subscriber = $this->createSubscriber();
    $this->addSubscriberField($subscriber, $cfDay, '24');

    $subscriberPayload = new SubscriberPayload($subscriber);
    $fields = $this->getFieldsMap();

    // check basics
    $label = $cfDay->getParams()['label'] ?? '';
    $this->assertCount(2, $fields);
    $this->assertSame('mailpoet:subscriber:custom-field:cf-day', $fields['cf-day']->getKey());
    $this->assertSame("Custom field: $label", $fields['cf-day']->getName());
    $this->assertSame([
      'options' => array_map(function (int $day) {
        return ['id' => $day, 'name' => "$day"];
      }, range(1, 31)),
    ], $fields['cf-day']->getArgs());

    // check types
    $this->assertSame('enum', $fields['cf-day']->getType());
    $this->assertSame('enum', $fields['cf-day-empty']->getType());

    // check values
    $this->assertSame(24, $fields['cf-day']->getValue($subscriberPayload));
    $this->assertSame(null, $fields['cf-day-empty']->getValue($subscriberPayload)); // not set
  }

  private function createCustomField(string $name, string $type, array $params = []): CustomFieldEntity {
    $customField = new CustomFieldEntity();
    $customField->setType($type);
    $customField->setName($name);
    $customField->setParams(['label' => $name] + $params);
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    return $customField;
  }

  private function createSubscriber(): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('test@example.com');
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function addSubscriberField(SubscriberEntity $subscriber, CustomFieldEntity $customField, $value): void {
    $subscriberCustomField = new SubscriberCustomFieldEntity($subscriber, $customField, $value);
    $subscriber->getSubscriberCustomFields()->add($subscriberCustomField);
    $this->entityManager->persist($subscriberCustomField);
    $this->entityManager->flush();
  }

  private function getFieldsMap(): array {
    $factory = $this->diContainer->get(SubscriberCustomFieldsFactory::class);
    $fields = [];
    foreach ($factory->getFields() as $field) {
      $fields[str_replace('mailpoet:subscriber:custom-field:', '', $field->getKey())] = $field;
    }
    return $fields;
  }
}
