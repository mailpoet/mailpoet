<?php

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;

class NewsletterOptionFieldCest {
  function _before() {
    $this->before_time = time();
    $this->data = array(
      'name' => 'Event',
      'newsletter_type' => 'welcome'
    );
    $this->option_field = NewsletterOptionField::create();
    $this->option_field->hydrate($this->data);
    $this->saved = $this->option_field->save();
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
    expect($this->saved->id() > 0)->true();
    expect($this->saved->getErrors())->false();
  }

  function itHasName() {
    $option_field = NewsletterOptionField::where('name', $this->data['name'])
      ->findOne();
    expect($option_field->name)->equals($this->data['name']);
  }

  function itHasNewsletterType() {
    $option_field = NewsletterOptionField::where('name', $this->data['name'])
      ->findOne();
    expect($option_field->newsletter_type)->equals($this->data['newsletter_type']);
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
    $option_field = NewsletterOptionField::where('name', $this->data['name'])
      ->findOne();
    $time_difference = strtotime($option_field->created_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }

  function itHasAnUpdatedAtOnCreation() {
    $option_field = NewsletterOptionField::where('name', $this->data['name'])
      ->findOne();
    $time_difference = strtotime($option_field->updated_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }

  function itKeepsTheCreatedAtOnUpdate() {
    $option_field = NewsletterOptionField::where('name', $this->data['name'])
      ->findOne();
    $old_created_at = $option_field->created_at;
    $option_field->name = 'new name';
    $option_field->save();
    expect($old_created_at)->equals($option_field->created_at);
  }

  function itUpdatesTheUpdatedAtOnUpdate() {
    $option_field = NewsletterOptionField::where('name', $this->data['name'])
      ->findOne();
    $update_time = time();
    $option_field->name = 'new name';
    $option_field->save();
    $time_difference = strtotime($option_field->updated_at) >= $update_time;
    expect($time_difference)->equals(true);
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
