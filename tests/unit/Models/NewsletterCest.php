<?php

use MailPoet\Models\Newsletter;

class NewsletterCest {
  function _before() {
    $this->before_time = time();
    $this->data = array(
      'subject' => 'new newsletter',
      'body' => 'body',
      'type' => 'standard',
      'preheader' => 'preaheader'
    );

    $newsletter = Newsletter::create();
    $newsletter->hydrate($this->data);
    $this->result = $newsletter->save();
  }

  function itCanBeCreated() {
    expect($this->result)->equals(true);
  }

  function itHasSubject() {
    $subscriber = Newsletter::where('subject', $this->data['subject'])
      ->findOne();
    expect($subscriber->subject)->equals($this->data['subject']);
  }

  function itHasType() {
    $subscriber = Newsletter::where('type', $this->data['type'])
      ->findOne();
    expect($subscriber->type)->equals($this->data['type']);
  }

  function itHasBody() {
    $subscriber = Newsletter::where('body', $this->data['body'])
      ->findOne();
    expect($subscriber->body)->equals($this->data['body']);
  }

  function itHasPreheader() {
    $subscriber = Newsletter::where('preheader', $this->data['preheader'])
      ->findOne();
    expect($subscriber->preheader)->equals($this->data['preheader']);
  }

  function itCanCreateOrUpdate() {
    $is_created = Newsletter::createOrUpdate(
      array(
        'subject' => 'new newsletter',
        'body' => 'body'
      ));
    expect($is_created)->equals(true);

    $newsletter = Newsletter::where('subject', 'new newsletter')
      ->findOne();
    expect($newsletter->subject)->equals('new newsletter');

    $is_updated = Newsletter::createOrUpdate(
      array(
        'id' => $newsletter->id,
        'subject' => 'updated newsletter',
        'body' => 'body'
      ));
    $newsletter = Newsletter::findOne($newsletter->id);
    expect($newsletter->subject)->equals('updated newsletter');
  }

  function itHasSearchFilter() {
    Newsletter::createOrUpdate(
      array(
        'subject' => 'search for "pineapple"',
        'body' => 'body'
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
