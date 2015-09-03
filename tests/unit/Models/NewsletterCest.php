<?php

use MailPoet\Models\Newsletter;

class NewsletterCest {
  function _before() {
    $this->before_time = time();
    $this->data = array(
      'subject' => 'My First Newsletter',
      'body' => 'a verrryyyyy long body :)'
    );

    $newsletter = Newsletter::create();
    $newsletter->hydrate($this->data);
    $this->saved = $newsletter->save();
  }

  function itCanBeCreated() {
    expect($this->saved)->equals(true);
  }

  function itHasASearchFilter() {
    $newsletter = Newsletter::filter('search', 'first')->findOne();
    expect($newsletter->subject)->equals($this->data['subject']);
  }

  function _after() {
    ORM::for_table(Newsletter::$_table)
      ->delete_many();
  }
}
