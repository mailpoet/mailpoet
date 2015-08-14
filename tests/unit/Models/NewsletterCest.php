<?php

use MailPoet\Models\Newsletter;

class NewsletterCest {
  
  function _before() {
    $this->before_time = time();
    $this->data = array(
      'subject' => 'My First Newsletter',
      'body'    => 'a verrryyyyy long body :)'
    );
    
    $newsletter = Newsletter::create();
    $newsletter->hydrate($this->data);
    $newsletter->save();
  }
  
  function itCanBeCreated() {
    $newsletter = Newsletter::where('subject', $this->data['subject'])
                            ->findOne();
    expect($newsletter->id)->notNull();
  }
  
  function subjectShouldValidate() {
    $conflict_newsletter = Newsletter::create();
    $conflict_newsletter->validateField('subject', '');
    expect($conflict_newsletter->getValidationErrors()[0])->equals('subject_is_blank');
  }
  
  function bodyShouldValidate() {
    $conflict_newsletter = Newsletter::create();
    $conflict_newsletter->validateField('body', '');
    expect($conflict_newsletter->getValidationErrors()[0])->equals('body_is_blank');
  }
  
  function itHasACreatedAtOnCreation() {
    $newsletter = Newsletter::where('subject', $this->data['subject'])
                            ->findOne();
    $time_difference = strtotime($newsletter->created_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }
  
  function itHasAnUpdatedAtOnCreation() {
    $newsletter = Newsletter::where('subject', $this->data['subject'])
                            ->findOne();
    $time_difference = strtotime($newsletter->updated_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }
  
  function itKeepsTheCreatedAtOnUpdate() {
    $newsletter = Newsletter::where('subject', $this->data['subject'])
                            ->findOne();
    $old_created_at = $newsletter->created_at;
    $newsletter->subject = $this->data['subject'];
    $newsletter->save();
    expect($old_created_at)->equals($newsletter->created_at);
  }
  
  function itUpdatesTheUpdatedAtOnUpdate() {
    $newsletter = Newsletter::where('subject', $this->data['subject'])
                            ->findOne();
    $update_time = time();
    $newsletter->subject = $this->data['subject'];
    $newsletter->save();
    $time_difference = strtotime($newsletter->updated_at) >= $update_time;
    expect($time_difference)->equals(true);
  }
  
  function _after() {
    $newsletter = Newsletter::where('subject', $this->data['subject'])
                            ->findOne()
                            ->delete();
  }
}
