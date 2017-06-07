<?php

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;

class NewsletterOptionTest extends MailPoetTest {
  public $data;

  function __construct() {
    parent::__construct();
    $this->data = array(
      'newsletter_id' => 1,
      'option_field_id' => 2,
      'value' => 'test'
    );
  }

  function testItCanCreateOrUpdateNewsletterOptionFieldRelation() {
    // it can create
    $data = $this->data;
    NewsletterOption::createOrUpdate($data);
    $newsletter_option = NewsletterOption::where('newsletter_id', $data['newsletter_id'])
      ->where('option_field_id', $data['option_field_id'])
      ->findOne();
    expect($newsletter_option->value)->equals($data['value']);

    // it can update
    $data['value'] = 'updated test';
    NewsletterOption::createOrUpdate($data);
    $newsletter_option = NewsletterOption::where('newsletter_id', $data['newsletter_id'])
      ->where('option_field_id', $data['option_field_id'])
      ->findOne();
    expect($newsletter_option->value)->equals($data['value']);
  }


  function _after() {
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
  }
}
