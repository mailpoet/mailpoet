<?php

use MailPoet\Models\NewsletterTemplate;

class NewsletterTemplateCest {
  function _before() {
    $this->before_time = time();
    $this->data = array(
      'name' => 'Some template',
      'description' => 'My nice template',
      'body' => '{content: {}, globalStyles: {}}',
    );

    $template = NewsletterTemplate::create();
    $template->hydrate($this->data);
    $this->result = $template->save();
  }

  function itCanBeCreated() {
    expect($this->result)->equals(true);
  }

  function itHasToBeValid() {
    $empty_model = NewsletterTemplate::create();
    expect($empty_model->save())->notEquals(true);
    $validations = $empty_model->getValidationErrors();
    expect(count($validations))->equals(2);
  }

  function itHasName() {
    $template = NewsletterTemplate::where('name', $this->data['name'])
      ->findOne();
    expect($template->name)->equals($this->data['name']);
  }

  function itHasDescription() {
    $template = NewsletterTemplate::where('description', $this->data['description'])
      ->findOne();
    expect($template->description)->equals($this->data['description']);
  }

  function itHasBody() {
    $template = NewsletterTemplate::where('body', $this->data['body'])
      ->findOne();
    expect($template->body)->equals($this->data['body']);
  }

  function itCanCreateOrUpdate() {
    $is_created = NewsletterTemplate::createOrUpdate(
      array(
        'name' => 'Another template',
        'description' => 'Another template description',
        'body' => '{content: {}, globalStyles: {}}',
      ));
    expect($is_created)->equals(true);

    $template = NewsletterTemplate::where('name', 'Another template')
      ->findOne();
    expect($template->name)->equals('Another template');

    $is_updated = NewsletterTemplate::createOrUpdate(
      array(
        'id' => $template->id,
        'name' => 'Another template updated',
        'body' => '{}'
      ));
    expect($is_updated)->equals(true);
    $template = NewsletterTemplate::findOne($template->id);
    expect($template->name)->equals('Another template updated');
  }

  function _after() {
    ORM::for_table(NewsletterTemplate::$_table)
      ->deleteMany();
  }
}
