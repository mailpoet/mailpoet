<?php

use MailPoet\Models\Newsletter;

class NewsletterCest {
  function _before() {
  }

  function itCanCreateOrUpdate() {
    $is_created = Newsletter::createOrUpdate(array(
      'subject' => 'new newsletter'
    ));
    expect($is_created)->equals(true);

    $newsletter = Newsletter::where('subject', 'new newsletter')->findOne();
    expect($newsletter->subject)->equals('new newsletter');

    $is_updated = Newsletter::createOrUpdate(array(
      'id' => $newsletter->id,
      'subject' => 'updated newsletter'
    ));
    $newsletter = Newsletter::where('subject', 'updated newsletter')->findOne();
    expect($newsletter->subject)->equals('updated newsletter');
  }

  function itHasASearchFilter() {
    Newsletter::createOrUpdate(array('subject' => 'search for "pineapple"'));
    $newsletter = Newsletter::filter('search', 'pineapple')->findOne();
    expect($newsletter->subject)->contains('pineapple');
  }

  function _after() {
    ORM::for_table(Newsletter::$_table)
      ->deleteMany();
  }
}
