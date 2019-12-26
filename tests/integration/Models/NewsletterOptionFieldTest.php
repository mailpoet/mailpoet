<?php

namespace MailPoet\Test\Models;

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoetVendor\Idiorm\ORM;

class NewsletterOptionFieldTest extends \MailPoetTest {
  public function _before() {
    parent::_before();
    $this->data = [
      'name' => 'event',
      'newsletter_type' => 'welcome',
    ];
    $option = NewsletterOptionField::create();
    $option->hydrate($this->data);
    $option->save();

    $this->option_field = NewsletterOptionField::findOne($option->id);

    $this->newsletter_data = [
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
    expect($this->option_field->id() > 0)->equals(true);
    expect($this->option_field->getErrors())->false();
  }

  public function testItHasName() {
    expect($this->option_field->name)->equals($this->data['name']);
  }

  public function testItHasNewsletterType() {
    expect($this->option_field->newsletter_type)
      ->equals($this->data['newsletter_type']);
  }

  public function testItHasToBeValid() {
    $invalid_newsletter_option = NewsletterOptionField::create();
    $result = $invalid_newsletter_option->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals('Please specify a name.');
    expect($errors[1])->equals('Please specify a newsletter type.');
  }

  public function testItHasACreatedAtOnCreation() {
    expect($this->option_field->created_at)->notNull();
  }

  public function testItHasAnUpdatedAtOnCreation() {
    $option_field = NewsletterOptionField::findOne($this->option_field->id);
    expect($option_field->updated_at)
      ->equals($option_field->created_at);
  }

  public function testItUpdatesTheUpdatedAtOnUpdate() {
    $option_field = NewsletterOptionField::findOne($this->option_field->id);
    $created_at = $option_field->created_at;

    sleep(1);

    $option_field->name = 'new name';
    $option_field->save();

    $updated_option_field = NewsletterOptionField::findOne($option_field->id);
    $is_time_updated = (
      $updated_option_field->updated_at > $updated_option_field->created_at
    );
    expect($is_time_updated)->true();
  }

  public function testItCanHaveManyNewsletters() {
    foreach ($this->newsletter_data as $data) {
      $newsletter = Newsletter::create();
      $newsletter->hydrate($data);
      $newsletter->save();
      $association = NewsletterOption::create();
      $association->newsletter_id = $newsletter->id;
      $association->option_field_id = $this->option_field->id;
      $association->save();
    }
    $option_field = NewsletterOptionField::findOne($this->option_field->id);
    $newsletters = $option_field->newsletters()
      ->findArray();
    expect(count($newsletters))->equals(2);
  }

  public function testItCanStoreOptionValue() {
    $newsletter = Newsletter::create();
    $newsletter->hydrate($this->newsletter_data[0]);
    $newsletter->save();
    $association = NewsletterOption::create();
    $association->newsletter_id = $newsletter->id;
    $association->option_field_id = $this->option_field->id;
    $association->value = 'list';
    $association->save();
    $option_field = NewsletterOptionField::findOne($this->option_field->id);
    $newsletter = $option_field->newsletters()
      ->findOne();
    expect($newsletter->value)->equals($association->value);
  }

  public function _after() {
    ORM::forTable(NewsletterOption::$_table)
      ->deleteMany();
    ORM::forTable(NewsletterOptionField::$_table)
      ->deleteMany();
    ORM::forTable(Newsletter::$_table)
      ->deleteMany();
  }
}
