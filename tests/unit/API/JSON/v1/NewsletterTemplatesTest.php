<?php
namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\NewsletterTemplates;
use MailPoet\Models\NewsletterTemplate;

class NewsletterTemplatesTest extends \MailPoetTest {
  function _before() {
    NewsletterTemplate::createOrUpdate(array(
      'name' => 'Template #1',
      'description' => 'My First Template',
      'body' => '{"key1": "value1"}'
    ));

    NewsletterTemplate::createOrUpdate(array(
      'name' => 'Template #2',
      'newsletter_id' => 1,
      'description' => 'My Second Template',
      'body' => '{"key2": "value2"}'
    ));
  }

  function testItCanGetANewsletterTemplate() {
    $template = NewsletterTemplate::where('name', 'Template #1')->findOne();

    $router = new NewsletterTemplates();
    $response = $router->get(/* missing id */);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])
      ->equals('This template does not exist.');

    $response = $router->get(array('id' => 'not_an_id'));
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])
      ->equals('This template does not exist.');

    $response = $router->get(array('id' => $template->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      $template->asArray()
    );
  }


  function testItCanGetAllNewsletterTemplates() {
    $templates = array_map(function($template) {
      return $template->asArray();
    }, NewsletterTemplate::findMany());

    $router = new NewsletterTemplates();
    $response = $router->getAll();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals($templates);
  }

  function testItCanSaveANewTemplate() {
    $template_data = array(
      'name' => 'Template #3',
      'description' => 'My Third Template',
      'body' => '{"key3": "value3"}'
    );

    $router = new NewsletterTemplates();
    $response = $router->save($template_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      NewsletterTemplate::findOne($response->data['id'])->asArray()
    );
  }

  function testItCanSaveANewTemplateAssociatedWithANewsletter() {
    $template_data = array(
      'newsletter_id' => 2,
      'name' => 'Template #3',
      'description' => 'My Third Template',
      'body' => '{"key3": "value3"}'
    );

    $router = new NewsletterTemplates();
    $response = $router->save($template_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      NewsletterTemplate::findOne($response->data['id'])->asArray()
    );
  }

  function testItCanUpdateTemplateAssociatedWithANewsletter() {
    $template_data = array(
      'newsletter_id' => 1,
      'name' => 'Template #2',
      'description' => 'My Updated Second Template',
      'body' => '{"key3": "value3"}'
    );

    $router = new NewsletterTemplates();
    $response = $router->save($template_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      NewsletterTemplate::findOne($response->data['id'])->asArray()
    );

    $template = NewsletterTemplate::whereEqual('newsletter_id', 1)->findOne();
    expect($response->data)->equals($template->asArray());
  }

  function testItCanDeleteANewsletterTemplate() {
    $template = NewsletterTemplate::where('name', 'Template #2')->findOne();
    expect($template->deleted_at)->null();

    $router = new NewsletterTemplates();
    $response = $router->delete(/* missing id */);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])
      ->equals('This template does not exist.');

    $response = $router->delete(array('id' => $template->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $deleted_template = NewsletterTemplate::findOne($template->id);
    expect($deleted_template)->false();
  }

  function _after() {
    NewsletterTemplate::deleteMany();
  }
}