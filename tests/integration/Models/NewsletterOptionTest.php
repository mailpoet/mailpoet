<?php

namespace MailPoet\Test\Models;

use MailPoet\Models\NewsletterOption;
use MailPoetVendor\Idiorm\ORM;

class NewsletterOptionTest extends \MailPoetTest {
  public $data;

  public function __construct() {
    parent::__construct();
    $this->data = [
      'newsletter_id' => 1,
      'option_field_id' => 2,
      'value' => 'test',
    ];
  }

  public function testItCanCreateOrUpdateNewsletterOptionFieldRelation() {
    // it can create
    $data = $this->data;
    NewsletterOption::createOrUpdate($data);
    $newsletterOption = NewsletterOption::where('newsletter_id', $data['newsletter_id'])
      ->where('option_field_id', $data['option_field_id'])
      ->findOne();
    assert($newsletterOption instanceof NewsletterOption);
    expect($newsletterOption->value)->equals($data['value']);

    // it can update
    $data['value'] = 'updated test';
    NewsletterOption::createOrUpdate($data);
    $newsletterOption = NewsletterOption::where('newsletter_id', $data['newsletter_id'])
      ->where('option_field_id', $data['option_field_id'])
      ->findOne();
    assert($newsletterOption instanceof NewsletterOption);
    expect($newsletterOption->value)->equals($data['value']);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
  }
}
