<?php

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;

class NewsletterOptionFieldCest {
  function _before() {
    $this->before_time = time();
    $this->data = array(
      'name' => 'Event',
      'newsletter_type' => 'welcome',
    );
    $this->optionField = NewsletterOptionField::create();
    $this->optionField->hydrate($this->data);
    $this->saved = $this->optionField->save();
    $this->newslettersData = array(
      array(
        'subject' => 'Test newsletter 1',
        'preheader' => '',
        'body' => '{}'
      ),
      array(
        'subject' => 'Test newsletter 2',
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
    $optionField = NewsletterOptionField::where('name', $this->data['name'])
      ->findOne();
    expect($optionField->name)->equals($this->data['name']);
  }

  function itHasNewsletterType() {
    $optionField = NewsletterOptionField::where('name', $this->data['name'])
      ->findOne();
    expect($optionField->newsletter_type)->equals($this->data['newsletter_type']);
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
    $optionField = NewsletterOptionField::where('name', $this->data['name'])
      ->findOne();
    $time_difference = strtotime($optionField->created_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }

  function itHasAnUpdatedAtOnCreation() {
    $optionField = NewsletterOptionField::where('name', $this->data['name'])
      ->findOne();
    $time_difference = strtotime($optionField->updated_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }

  function itKeepsTheCreatedAtOnUpdate() {
    $optionField = NewsletterOptionField::where('name', $this->data['name'])
      ->findOne();
    $old_created_at = $optionField->created_at;
    $optionField->name = 'new name';
    $optionField->save();
    expect($old_created_at)->equals($optionField->created_at);
  }

  function itUpdatesTheUpdatedAtOnUpdate() {
    $optionField = NewsletterOptionField::where('name', $this->data['name'])
      ->findOne();
    $update_time = time();
    $optionField->name = 'new name';
    $optionField->save();
    $time_difference = strtotime($optionField->updated_at) >= $update_time;
    expect($time_difference)->equals(true);
  }

  function itCanHaveManyNewsletters() {
    foreach ($this->newslettersData as $data) {
      $newsletter = Newsletter::create();
      $newsletter->hydrate($data);
      $newsletter->save();
      $association = NewsletterOption::create();
      $association->newsletter_id = $newsletter->id;
      $association->option_field_id = $this->optionField->id;
      $association->save();
    }
    $optionField = NewsletterOptionField::findOne($this->optionField->id);
    $newsletters = $optionField->newsletters()
      ->findArray();
    expect(count($newsletters))->equals(2);
  }

  function itCanStoreOptionValue() {
    $newsletter = Newsletter::create();
    $newsletter->hydrate($this->newslettersData[0]);
    $newsletter->save();
    $association = NewsletterOption::create();
    $association->newsletter_id = $newsletter->id;
    $association->option_field_id = $this->optionField->id;
    $association->value = 'list';
    $association->save();
    $optionField = NewsletterOptionField::findOne($this->optionField->id);
    $newsletter = $optionField->newsletters()
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
