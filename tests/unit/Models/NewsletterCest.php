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

  function _after() {
    $newsletter =
      Newsletter::where(
        'subject',
        $this->data['subject']
      )->findOne()->delete();
  }
}
