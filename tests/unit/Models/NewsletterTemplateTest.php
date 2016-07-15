<?php

use MailPoet\Models\NewsletterTemplate;

class NewsletterTemplateTest extends MailPoetTest {
  function _before() {
    $this->data = array(
      'name' => 'Some template',
      'description' => 'My nice template',
      'body' => '{}',
    );

    $template = NewsletterTemplate::create();
    $template->hydrate($this->data);
    $this->saved = $template->save();
  }

  function testItCanBeCreated() {
    expect($this->saved->id() > 0)->true();
    expect($this->saved->getErrors())->false();
  }

  function testItHasToBeValid() {
    $invalid_newsletter_template = NewsletterTemplate::create();
    $result = $invalid_newsletter_template->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals('Please specify a name');
    expect($errors[1])->equals('The template body cannot be empty');
  }

  function testItHasName() {
    $template = NewsletterTemplate::where('name', $this->data['name'])
      ->findOne();
    expect($template->name)->equals($this->data['name']);
  }

  function testItHasDescription() {
    $template = NewsletterTemplate::where('description', $this->data['description'])
      ->findOne();
    expect($template->description)->equals($this->data['description']);
  }

  function testItHasBody() {
    $template = NewsletterTemplate::where('body', $this->data['body'])
      ->findOne();
    expect($template->body)->equals($this->data['body']);
  }

  function testItCanCreateOrUpdate() {
    $created_template = NewsletterTemplate::createOrUpdate(
      array(
        'name' => 'Another template',
        'description' => 'Another template description',
        'body' => '{content: {}, globalStyles: {}}',
      ));
    expect($created_template->id() > 0)->true();
    expect($created_template->getErrors())->false();

    $template = NewsletterTemplate::where('name', 'Another template')
      ->findOne();
    expect($template->name)->equals('Another template');

    $updated_template = NewsletterTemplate::createOrUpdate(
      array(
        'id' => $template->id,
        'name' => 'Another template updated',
        'body' => '{}'
      ));
    expect($updated_template->id() > 0)->true();
    expect($updated_template->getErrors())->false();

    $template = NewsletterTemplate::findOne($template->id);
    expect($template->name)->equals('Another template updated');
  }

  function _after() {
    ORM::for_table(NewsletterTemplate::$_table)
      ->deleteMany();
  }
}
