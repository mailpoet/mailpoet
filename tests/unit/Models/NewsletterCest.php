<?php

use MailPoet\Models\Newsletter;

class NewsletterCest {
  function _before() {
    $this->before_time = time();
    $this->data = array(
      'subject'  => 'new newsletter',
      'body' => 'json...'
    );

    $newsletter = Newsletter::create();
    $newsletter->hydrate($this->data);
    $this->result = $newsletter->save();
  }

  function itHasToBeValid() {
    expect($this->result)->equals(true);
    $empty_model = Newsletter::create();
    expect($empty_model->save())->notEquals(true);
    $validations = $empty_model->getValidationErrors();
    expect(count($validations))->equals(2);
  }

  function itCanCreateOrUpdate() {
    $is_created = Newsletter::createOrUpdate(
      array(
        'subject' => 'new newsletter',
        'body' => 'json...'
      ));
    expect($is_created)->equals(true);

    $newsletter = Newsletter::where('subject', 'new newsletter')
      ->findOne();
    expect($newsletter->subject)->equals('new newsletter');

    $is_updated = Newsletter::createOrUpdate(
      array(
        'id' => $newsletter->id,
        'subject' => 'updated newsletter',
        'body' => 'json...'
      ));
    $newsletter = Newsletter::findOne($newsletter->id);
    expect($newsletter->subject)->equals('updated newsletter');
  }

  function itHasASearchFilter() {
    Newsletter::createOrUpdate(
      array(
        'subject' => 'search for "pineapple"',
        'body' => 'json...'
      ));
    $newsletter = Newsletter::filter('search', 'pineapple')
      ->findOne();
    expect($newsletter->subject)->contains('pineapple');
  }

  function _after() {
    ORM::for_table(Newsletter::$_table)
      ->deleteMany();
  }
}
