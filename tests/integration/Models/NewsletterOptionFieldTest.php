<?php

namespace MailPoet\Test\Models;

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;

class NewsletterOptionFieldTest extends \MailPoetTest {
  public $data;
  public $newsletterData;
  public $optionField;

  public function _before() {
    parent::_before();
    $this->data = [
      'name' => 'event',
      'newsletter_type' => 'welcome',
    ];
    $option = NewsletterOptionField::create();
    $option->hydrate($this->data);
    $option->save();

    $this->optionField = NewsletterOptionField::findOne($option->id);

    $this->newsletterData = [
      [
        'subject' => 'Test newsletter 1',
        'type' => 'standard',
        'preheader' => '',
        'body' => '{}',
      ],
      [
        'subject' => 'Test newsletter 2',
        'type' => 'standard',
        'preheader' => 'A newsletter',
        'body' => '{}',
      ],
    ];
  }

  public function testItCanBeCreated() {
    expect($this->optionField->id() > 0)->equals(true);
    expect($this->optionField->getErrors())->false();
  }

  public function testItHasName() {
    expect($this->optionField->name)->equals($this->data['name']);
  }

  public function testItHasNewsletterType() {
    expect($this->optionField->newsletter_type)
      ->equals($this->data['newsletter_type']);
  }

  public function testItHasToBeValid() {
    $invalidNewsletterOption = NewsletterOptionField::create();
    $result = $invalidNewsletterOption->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals('Please specify a name.');
    expect($errors[1])->equals('Please specify a newsletter type.');
  }

  public function testItHasACreatedAtOnCreation() {
    expect($this->optionField->created_at)->notNull();
  }

  public function testItHasAnUpdatedAtOnCreation() {
    $optionField = NewsletterOptionField::findOne($this->optionField->id);
    assert($optionField instanceof NewsletterOptionField);
    expect($optionField->updatedAt)->equals($optionField->createdAt);
  }

  public function testItUpdatesTheUpdatedAtOnUpdate() {
    $optionField = NewsletterOptionField::findOne($this->optionField->id);
    assert($optionField instanceof NewsletterOptionField);
    $createdAt = $optionField->createdAt;

    sleep(1);

    $optionField->name = 'new name';
    $optionField->save();

    $updatedOptionField = NewsletterOptionField::findOne($optionField->id);
    assert($updatedOptionField instanceof NewsletterOptionField);
    $isTimeUpdated = (
      $updatedOptionField->updatedAt > $updatedOptionField->createdAt
    );
    expect($isTimeUpdated)->true();
  }

  public function testItCanHaveManyNewsletters() {
    foreach ($this->newsletterData as $data) {
      $newsletter = Newsletter::create();
      $newsletter->hydrate($data);
      $newsletter->save();
      $association = NewsletterOption::create();
      $association->newsletterId = $newsletter->id;
      $association->optionFieldId = $this->optionField->id;
      $association->save();
    }
    $optionField = NewsletterOptionField::findOne($this->optionField->id);
    assert($optionField instanceof NewsletterOptionField);
    $newsletters = $optionField->newsletters()
      ->findArray();
    expect(count($newsletters))->equals(2);
  }

  public function testItCanStoreOptionValue() {
    $newsletter = Newsletter::create();
    $newsletter->hydrate($this->newsletterData[0]);
    $newsletter->save();
    $association = NewsletterOption::create();
    $association->newsletterId = $newsletter->id;
    $association->optionFieldId = $this->optionField->id;
    $association->value = 'list';
    $association->save();
    $optionField = NewsletterOptionField::findOne($this->optionField->id);
    assert($optionField instanceof NewsletterOptionField);
    $newsletter = $optionField->newsletters()
      ->findOne();
    expect($newsletter->value)->equals($association->value);
  }

  public function _after() {
    NewsletterOption::deleteMany();
    NewsletterOptionField::deleteMany();
    Newsletter::deleteMany();
  }
}
