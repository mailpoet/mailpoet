<?php
use \MailPoet\API\NewsletterTemplates;
use \MailPoet\Models\NewsletterTemplate;

class NewsletterTemplatesTest extends MailPoetTest {
  function _before() {
    NewsletterTemplate::createOrUpdate(array(
      'name' => 'Template #1',
      'description' => 'My First Template',
      'body' => '{"key1": "value1"}'
    ));

    NewsletterTemplate::createOrUpdate(array(
      'name' => 'Template #2',
      'description' => 'My Second Template',
      'body' => '{"key2": "value2"}'
    ));
  }

  function testItCanGetANewsletterTemplate() {
    $template = NewsletterTemplate::where('name', 'Template #1')->findOne();

    $router = new NewsletterTemplates();
    $response = $router->get(/* missing id */);
    expect($response)->false();

    $response = $router->get('not_an_id');
    expect($response)->false();

    $response = $router->get($template->id());
    expect($response['name'])->equals('Template #1');
    expect($response['body']['key1'])->equals('value1');
  }


  function testItCanGetAllNewsletterTemplates() {
    $templates = NewsletterTemplate::findArray();

    $router = new NewsletterTemplates();
    $response = $router->getAll();
    expect($response)->count(2);

    expect($response[0]['name'])->equals('Template #1');
    expect($response[0]['body']['key1'])->equals('value1');

    expect($response[1]['name'])->equals('Template #2');
    expect($response[1]['body']['key2'])->equals('value2');
  }

  function testItCanSaveANewsletterTemplate() {
    $template_data = array(
      'name' => 'Template #3',
      'description' => 'My Third Template',
      'body' => '{"key3": "value3"}'
    );

    $router = new NewsletterTemplates();
    $response = $router->save($template_data);
    expect($response)->true();

    $template = NewsletterTemplate::where('name', 'Template #3')->findOne();
    expect($template->name)->equals('Template #3');
    expect($template->description)->equals('My Third Template');
    expect($template->body)->equals('{"key3": "value3"}');
  }

  function testItCanDeleteANewsletterTemplate() {
    $template = NewsletterTemplate::where('name', 'Template #2')->findOne();
    expect($template->deleted_at)->null();

    $router = new NewsletterTemplates();
    $response = $router->delete($template->id());
    expect($response)->true();

    $deleted_template = NewsletterTemplate::findOne($template->id());
    expect($deleted_template)->false();
  }

  function _after() {
    NewsletterTemplate::deleteMany();
  }
}