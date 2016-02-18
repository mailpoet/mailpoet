<?php

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;

class NewsletterOptionFieldCest {
  function _before() {
    $this->data = array(
      'name' => 'Event',
      'newsletter_type' => 'welcome'
    );
    $option = NewsletterOptionField::create();
    $option->hydrate($this->data);
    $this->option_field = $option->save();

    $this->newsletter_data = array(
      array(
        'subject' => 'Test newsletter 1',
        'type' => 'standard',
        'preheader' => '',
        'body' => '{}'
      ),
      array(
        'subject' => 'Test newsletter 2',
        'type' => 'standard',
        'preheader' => 'A newsletter',
        'body' => '{}'
      )
    );
  }

  function itCanBeCreated() {
    expect($this->option_field->id() > 0)->true();
    expect($this->option_field->getErrors())->false();
  }

  function itHasName() {
    expect($this->option_field->name)->equals($this->data['name']);
  }

  function itHasNewsletterType() {
    expect($this->option_field->newsletter_type)
      ->equals($this->data['newsletter_type']);
  }

  function itHasToBeValid() {
    $invalid_newsletter_option = NewsletterOptionField::create();
    $result = $invalid_newsletter_option->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals('You need to specify a name.');
    expect($errors[1])->equals('You need to specify a newsletter type.');
  }

  function itHasACreatedAtOnCreation() {
    $option_field = NewsletterOptionField::findOne($this->option_field->id);
    expect($option_field->created_at)->notNull();
    expect($option_field->created_at)->notEquals('0000-00-00 00:00:00');
  }

  function itHasAnUpdatedAtOnCreation() {
    $option_field = NewsletterOptionField::findOne($this->option_field->id);
    expect($option_field->updated_at)
      ->equals($option_field->created_at);
  }

  function itUpdatesTheUpdatedAtOnUpdate() {
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

  function itCanHaveManyNewsletters() {
    foreach($this->newsletter_data as $data) {
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

  function itCanStoreOptionValue() {
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

  function _after() {
    ORM::forTable(NewsletterOption::$_table)
      ->deleteMany();
    ORM::forTable(NewsletterOptionField::$_table)
      ->deleteMany();
    ORM::forTable(Newsletter::$_table)
      ->deleteMany();
  }
}
