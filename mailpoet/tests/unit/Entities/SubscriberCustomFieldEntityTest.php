<?php declare(strict_types = 1);

namespace MailPoet\Entities;

class SubscriberCustomFieldEntityTest extends \MailPoetUnitTest {
  public function testValueYearMonthDayIsFormatted(): void {
    $subscriber = new SubscriberEntity();
    $customField = new CustomFieldEntity();
    $customField->setType(CustomFieldEntity::TYPE_DATE);
    $customField->setParams([
      'date_format' => 'MM/DD/YYYY',
      'date_type' => 'year_month_day',
    ]);
    $subscriberCustomField = new SubscriberCustomFieldEntity($subscriber, $customField, [
      'year' => 2010,
      'month' => 7,
      'day' => 10,
    ]);
    expect($subscriberCustomField->getValue())->equals('2010-07-10 00:00:00');
  }

  public function testValueYearMonthIsFormatted(): void {
    $subscriber = new SubscriberEntity();
    $customField = new CustomFieldEntity();
    $customField->setType(CustomFieldEntity::TYPE_DATE);
    $customField->setParams([
      'date_format' => 'YYYY/MM',
      'date_type' => 'year_month',
    ]);
    $subscriberCustomField = new SubscriberCustomFieldEntity($subscriber, $customField, [
      'year' => 2010,
      'month' => 2,
    ]);
    expect($subscriberCustomField->getValue())->equals('2010-02-01 00:00:00');
  }

  public function testValueYearIsFormatted(): void {
    $subscriber = new SubscriberEntity();
    $customField = new CustomFieldEntity();
    $customField->setType(CustomFieldEntity::TYPE_DATE);
    $customField->setParams([
      'date_format' => 'YYYY',
      'date_type' => 'year',
    ]);
    $subscriberCustomField = new SubscriberCustomFieldEntity($subscriber, $customField, [
      'year' => 1994,
    ]);
    expect($subscriberCustomField->getValue())->equals('1994-01-01 00:00:00');
  }

  public function testValueStringIsFormatted(): void {
    $subscriber = new SubscriberEntity();
    $customField = new CustomFieldEntity();
    $customField->setType(CustomFieldEntity::TYPE_TEXT);
    $subscriberCustomField = new SubscriberCustomFieldEntity($subscriber, $customField, 'some value');
    expect($subscriberCustomField->getValue())->equals('some value');
  }
}
