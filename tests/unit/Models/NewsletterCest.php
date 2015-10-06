<?php

use MailPoet\Models\Newsletter;
use MailPoet\Models\Segment;
use MailPoet\Models\NewsletterSegment;

class NewsletterCest {
  function _before() {
    $this->before_time = time();
    $this->data = array(
      'subject' => 'new newsletter',
      'body' => 'body',
      'type' => 'standard',
      'preheader' => 'preheader'
    );

    $newsletter = Newsletter::create();
    $newsletter->hydrate($this->data);
    $this->result = $newsletter->save();
  }

  function itCanBeCreated() {
    expect($this->result)->equals(true);
  }

  function itHasSubject() {
    $newsletter = Newsletter::where('subject', $this->data['subject'])
      ->findOne();
    expect($newsletter->subject)->equals($this->data['subject']);
  }

  function itHasType() {
    $newsletter = Newsletter::where('type', $this->data['type'])
      ->findOne();
    expect($newsletter->type)->equals($this->data['type']);
  }

  function itHasBody() {
    $newsletter = Newsletter::where('body', $this->data['body'])
      ->findOne();
    expect($newsletter->body)->equals($this->data['body']);
  }

  function itHasPreheader() {
    $newsletter = Newsletter::where('preheader', $this->data['preheader'])
      ->findOne();
    expect($newsletter->preheader)->equals($this->data['preheader']);
  }

  function itCanHaveASegment() {
    $segmentData = array(
      'name' => 'my first list'
    );

    $segment = Segment::create();
    $segment->hydrate($segmentData);
    $segment->save();

    $newsletter = Newsletter::create();
    $newsletter->hydrate($this->data);
    $newsletter->save();

    $association = NewsletterSegment::create();
    $association->newsletter_id = $newsletter->id();
    $association->segment_id = $segment->id();
    $association->save();

    $newsletter = Newsletter::findOne($newsletter->id);
    $newsletterSegment = $newsletter->segments()->findOne();
    expect($newsletterSegment->id)->equals($segment->id);
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
