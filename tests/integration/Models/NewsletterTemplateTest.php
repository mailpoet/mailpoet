<?php

namespace MailPoet\Test\Models;

use MailPoet\Models\NewsletterTemplate;

class NewsletterTemplateTest extends \MailPoetTest {
  public $data;
  public $saved;

  public function _before() {
    parent::_before();
    $this->data = [
      'name' => 'Some template',
      'body' => '{}',
    ];

    $template = NewsletterTemplate::create();
    $template->hydrate($this->data);
    $this->saved = $template->save();
  }

  public function testItCanBeCreated() {
    expect($this->saved->id() > 0)->true();
    expect($this->saved->getErrors())->false();
  }

  public function testItHasToBeValid() {
    $invalidNewsletterTemplate = NewsletterTemplate::create();
    $result = $invalidNewsletterTemplate->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals('Please specify a name.');
    expect($errors[1])->equals('The template body cannot be empty.');
  }

  public function testItHasName() {
    $template = NewsletterTemplate::where('name', $this->data['name'])
      ->findOne();
    expect($template->name)->equals($this->data['name']);
  }

  public function testItHasBody() {
    $template = NewsletterTemplate::where('body', $this->data['body'])
      ->findOne();
    expect($template->body)->equals($this->data['body']);
  }

  public function testItCanCreateOrUpdate() {
    $createdTemplate = NewsletterTemplate::createOrUpdate(
      [
        'name' => 'Another template',
        'body' => '{content: {}, globalStyles: {}}',
      ]);
    expect($createdTemplate->id() > 0)->true();
    expect($createdTemplate->getErrors())->false();

    $template = NewsletterTemplate::where('name', 'Another template')
      ->findOne();
    expect($template->name)->equals('Another template');

    $updatedTemplate = NewsletterTemplate::createOrUpdate(
      [
        'id' => $template->id,
        'name' => 'Another template updated',
        'body' => '{}',
      ]);
    expect($updatedTemplate->id() > 0)->true();
    expect($updatedTemplate->getErrors())->false();

    $template = NewsletterTemplate::findOne($template->id);
    expect($template->name)->equals('Another template updated');
  }

  public function testItCanCleanRecentlySent() {
    $total = NewsletterTemplate::RECENTLY_SENT_COUNT + 5;
    for ($i = 0; $i < $total; $i++) {
      NewsletterTemplate::createOrUpdate([
        'name' => 'Testing template ' . $i,
        'body' => '{content: {}, globalStyles: {}}',
        'categories' => NewsletterTemplate::RECENTLY_SENT_CATEGORIES,
      ]);
    }

    NewsletterTemplate::cleanRecentlySent([]);
    $count = NewsletterTemplate::where(
      'categories', NewsletterTemplate::RECENTLY_SENT_CATEGORIES
      )->count();
    expect($count)->equals($total);

    NewsletterTemplate::cleanRecentlySent([
      'categories' => NewsletterTemplate::RECENTLY_SENT_CATEGORIES,
    ]);
    $count = NewsletterTemplate::where(
      'categories', NewsletterTemplate::RECENTLY_SENT_CATEGORIES
      )->count();
    expect($count)->equals(NewsletterTemplate::RECENTLY_SENT_COUNT);

    $first = NewsletterTemplate::where(
      'categories', NewsletterTemplate::RECENTLY_SENT_CATEGORIES
      )->findOne();
    expect($first->name)->equals('Testing template 5');
  }

  public function _after() {
    NewsletterTemplate::deleteMany();
  }
}
