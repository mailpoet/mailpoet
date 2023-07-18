<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;

class MailPoetCustomFieldsTest extends \MailPoetTest {

  /** @var MailPoetCustomFields */
  private $filter;

  /** @var SubscriberEntity[] */
  private $subscribers = [];

  public function _before(): void {
    $this->filter = $this->diContainer->get(MailPoetCustomFields::class);
    $this->subscribers = [];
    $this->subscribers[] = $this->createSubscriber('subscriber1@example.com');
    $this->subscribers[] = $this->createSubscriber('subscriber2@example.com');
    $this->subscribers[] = $this->createSubscriber('subscriber3@example.com');
    $this->entityManager->flush();
  }

  public function testItFiltersSubscribersWithTextEquals(): void {
    $subscriber = $this->subscribers[2];
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_TEXT);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($subscriber, $customField, 'some value'));
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_TEXT,
      'operator' => 'equals',
      'value' => 'some value',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$subscriber->getEmail()], $emails);
  }

  public function testItFiltersSubscribersWithTextContains(): void {
    $subscriber = $this->subscribers[1];
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_TEXT);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($subscriber, $customField, 'some value'));
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_TEXT,
      'operator' => 'contains',
      'value' => 'value',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$subscriber->getEmail()], $emails);
  }

  public function testItFiltersSubscribersWithTextNotContains(): void {
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_TEXT);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[0], $customField, 'some value'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[1], $customField, 'different value'));
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_TEXT,
      'operator' => 'not_contains',
      'value' => 'me val',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[1]->getEmail()], $emails);
  }

  public function testItFiltersSubscribersTextNotEquals(): void {
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_TEXT);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[1], $customField, 'something else'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[0], $customField, 'some value'));
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_TEXT,
      'operator' => 'not_equals',
      'value' => 'some value',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[1]->getEmail(), $this->subscribers[2]->getEmail()], $emails);
  }

  public function testItFiltersSubscribersTextMoreThan(): void {
    $subscriber = $this->subscribers[1];
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_TEXT);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($subscriber, $customField, '3'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[0], $customField, '1'));
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_TEXT,
      'operator' => 'more_than',
      'value' => '2',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$subscriber->getEmail()], $emails);
  }

  public function testItFiltersSubscribersTextLessThan(): void {
    $subscriber = $this->subscribers[1];
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_TEXT);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($subscriber, $customField, '1'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[0], $customField, '3'));
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_TEXT,
      'operator' => 'less_than',
      'value' => '2',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$subscriber->getEmail()], $emails);
  }

  public function testItFiltersRadio(): void {
    $subscriber = $this->subscribers[1];
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_RADIO);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($subscriber, $customField, 'Option 2'));
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_RADIO,
      'operator' => 'equals',
      'value' => 'Option 2',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$subscriber->getEmail()], $emails);
  }

  public function testItFiltersCheckboxChecked(): void {
    $subscriber = $this->subscribers[1];
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_CHECKBOX);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($subscriber, $customField, '1'));
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_CHECKBOX,
      'operator' => 'equals',
      'value' => '1',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$subscriber->getEmail()], $emails);
  }

  public function testItFiltersCheckboxUnChecked(): void {
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_CHECKBOX);
    $this->entityManager->persist($customField);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[0], $customField, '1'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[1], $customField, '0'));
    $this->entityManager->flush();
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_CHECKBOX,
      'operator' => 'equals',
      'value' => '0',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[1]->getEmail()], $emails);
  }

  public function testItFiltersMonthDate(): void {
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_DATE);
    $this->entityManager->persist($customField);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[0], $customField, '2021-04-01 00:00:00'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[1], $customField, '2020-04-01 00:00:00'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[2], $customField, '2020-05-01 00:00:00'));
    $this->entityManager->flush();
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_DATE,
      'date_type' => 'month',
      'value' => '2017-04-01 00:00:00',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[0]->getEmail(), $this->subscribers[1]->getEmail()], $emails);
  }

  public function testItFiltersDateYear(): void {
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_DATE);
    $this->entityManager->persist($customField);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[0], $customField, '2017-03-14 00:00:00'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[1], $customField, '2017-04-01 00:00:00'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[2], $customField, '2020-05-01 00:00:00'));
    $this->entityManager->flush();
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_DATE,
      'date_type' => 'year',
      'value' => '2017-01-01 00:00:00',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[0]->getEmail(), $this->subscribers[1]->getEmail()], $emails);
  }

  public function testItFiltersDateYearBefore(): void {
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_DATE);
    $this->entityManager->persist($customField);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[0], $customField, '2016-03-14 00:00:00'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[1], $customField, '2017-04-01 00:00:00'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[2], $customField, '2020-05-01 00:00:00'));
    $this->entityManager->flush();
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_DATE,
      'date_type' => 'year',
      'operator' => 'before',
      'value' => '2017-01-01 00:00:00',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[0]->getEmail()], $emails);
  }

  public function testItFiltersDateMonthYear(): void {
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_DATE);
    $this->entityManager->persist($customField);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[0], $customField, '2016-04-01 00:00:00'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[1], $customField, '2017-04-01 00:00:00'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[2], $customField, '2020-05-01 00:00:00'));
    $this->entityManager->flush();
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_DATE,
      'date_type' => 'year_month',
      'value' => '2017-04-01 00:00:00',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[1]->getEmail()], $emails);
  }

  public function testItFiltersDateMonthYearBefore(): void {
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_DATE);
    $this->entityManager->persist($customField);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[0], $customField, '2016-04-01 00:00:00'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[1], $customField, '2017-04-01 00:00:00'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[2], $customField, '2020-05-01 00:00:00'));
    $this->entityManager->flush();
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_DATE,
      'date_type' => 'year_month',
      'operator' => 'before',
      'value' => '2017-04-01 00:00:00',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[0]->getEmail()], $emails);
  }

  public function testItFiltersFullDate(): void {
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_DATE);
    $this->entityManager->persist($customField);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[0], $customField, '2016-04-01 00:00:00'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[1], $customField, '2017-04-01 00:00:00'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[2], $customField, '2020-05-01 00:00:00'));
    $this->entityManager->flush();
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_DATE,
      'date_type' => 'year_month_day',
      'value' => '2017-04-01 00:00:00',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[1]->getEmail()], $emails);
  }

  public function testItFiltersFullDateAfter(): void {
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_DATE);
    $this->entityManager->persist($customField);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[0], $customField, '2016-04-01 00:00:00'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[1], $customField, '2017-04-01 00:00:00'));
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[2], $customField, '2020-05-01 00:00:00'));
    $this->entityManager->flush();
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_DATE,
      'date_type' => 'year_month_day',
      'operator' => 'after',
      'value' => '2017-03-02 00:00:00',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($segmentFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[1]->getEmail(), $this->subscribers[2]->getEmail()], $emails);
  }

  public function testTextInputWorksWithBlankOptions(): void {
    $subscriber = $this->subscribers[1];
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_TEXT);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($subscriber, $customField, '1'));
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    $blankData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_TEXT,
      'operator' => 'is_blank',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($blankData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[0]->getEmail(), $this->subscribers[2]->getEmail()], $emails);
    $notBlankData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_TEXT,
      'operator' => 'is_not_blank',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($notBlankData, $this->filter);
    $this->assertEqualsCanonicalizing([$subscriber->getEmail()], $emails);
  }

  public function testTextAreaWorksWithBlankOptions(): void {
    $subscriber = $this->subscribers[1];
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_TEXT);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($subscriber, $customField, '1'));
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    $blankData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_TEXT,
      'operator' => 'is_blank',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($blankData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[0]->getEmail(), $this->subscribers[2]->getEmail()], $emails);
    $notBlankData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_TEXT,
      'operator' => 'is_not_blank',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($notBlankData, $this->filter);
    $this->assertEqualsCanonicalizing([$subscriber->getEmail()], $emails);
  }

  public function testFullDateWorksWithBlankOptions(): void {
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_DATE);
    $this->entityManager->persist($customField);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[1], $customField, '2017-04-01 00:00:00'));
    $this->entityManager->flush();
    $blankData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_DATE,
      'date_type' => 'year_month_day',
      'operator' => 'is_blank',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($blankData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[0]->getEmail(), $this->subscribers[2]->getEmail()], $emails);
    $notBlankData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_DATE,
      'date_type' => 'year_month_day',
      'operator' => 'is_not_blank',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($notBlankData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[1]->getEmail()], $emails);
  }

  public function testYearWorksWithBlankOptions(): void {
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_DATE);
    $this->entityManager->persist($customField);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[1], $customField, '2017-04-01 00:00:00'));
    $this->entityManager->flush();
    $blankFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_DATE,
      'date_type' => 'year',
      'operator' => 'is_blank',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($blankFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[0]->getEmail(), $this->subscribers[2]->getEmail()], $emails);
    $notBlankFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_DATE,
      'date_type' => 'year',
      'operator' => 'is_not_blank',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($notBlankFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[1]->getEmail()], $emails);
  }

  public function testDateMonthWorksWithBlankOptions(): void {
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_DATE);
    $this->entityManager->persist($customField);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($this->subscribers[1], $customField, '2017-04-01 00:00:00'));
    $this->entityManager->flush();
    $blankFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_DATE,
      'date_type' => 'month',
      'operator' => 'is_blank',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($blankFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[0]->getEmail(), $this->subscribers[2]->getEmail()], $emails);
    $notBlankFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_DATE,
      'date_type' => 'month',
      'operator' => 'is_not_blank',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($notBlankFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[1]->getEmail()], $emails);
  }

  public function testRadioButtonsWorksWithBlankOptions(): void {
    $subscriber = $this->subscribers[1];
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_RADIO);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($subscriber, $customField, 'Option 2'));
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    $blankFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_RADIO,
      'operator' => 'is_blank',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($blankFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[0]->getEmail(), $this->subscribers[2]->getEmail()], $emails);
    $notBlankFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_RADIO,
      'operator' => 'is_not_blank',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($notBlankFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$subscriber->getEmail()], $emails);
  }

  public function testCheckboxWorksWithBlankOptions(): void {
    $subscriber = $this->subscribers[1];
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_CHECKBOX);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($subscriber, $customField, '1'));
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    $blankFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_CHECKBOX,
      'operator' => 'is_blank',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($blankFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[0]->getEmail(), $this->subscribers[2]->getEmail()], $emails);
    $notBlankFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_CHECKBOX,
      'operator' => 'is_not_blank',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($notBlankFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$subscriber->getEmail()], $emails);
  }

  public function testSelectWorksWithBlankOptions(): void {
    $subscriber = $this->subscribers[1];
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_SELECT);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($subscriber, $customField, 'Option 2'));
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    $blankFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_SELECT,
      'operator' => 'is_blank',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($blankFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$this->subscribers[0]->getEmail(), $this->subscribers[2]->getEmail()], $emails);
    $notBlankFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE, [
      'custom_field_id' => $customField->getId(),
      'custom_field_type' => CustomFieldEntity::TYPE_SELECT,
      'operator' => 'is_not_blank',
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($notBlankFilterData, $this->filter);
    $this->assertEqualsCanonicalizing([$subscriber->getEmail()], $emails);
  }

  private function createSubscriber(string $email): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setLastName('Last');
    $subscriber->setFirstName('First');
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriber);
    return $subscriber;
  }

  private function createCustomField(string $type, array $params = []): CustomFieldEntity {
    $customField = new CustomFieldEntity();
    $customField->setType($type);
    $customField->setParams($params);
    $customField->setName('custom field' . rand());
    return $customField;
  }
}
