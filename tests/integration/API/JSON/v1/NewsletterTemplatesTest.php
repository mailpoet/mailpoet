<?php

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\NewsletterTemplates;
use MailPoet\Models\NewsletterTemplate;

class NewsletterTemplatesTest extends \MailPoetTest {
  function _before() {
    parent::_before();
    NewsletterTemplate::deleteMany();
    NewsletterTemplate::createOrUpdate([
      'name' => 'Template #1',
      'body' => '{"key1": "value1"}',
    ]);

    NewsletterTemplate::createOrUpdate([
      'name' => 'Template #2',
      'newsletter_id' => 1,
      'body' => '{"key2": "value2"}',
    ]);
  }

  function testItCanGetANewsletterTemplate() {
    $template = NewsletterTemplate::where('name', 'Template #1')->findOne();

    $router = new NewsletterTemplates();
    $response = $router->get(/* missing id */);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])
      ->equals('This template does not exist.');

    $response = $router->get(['id' => 'not_an_id']);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])
      ->equals('This template does not exist.');

    $response = $router->get(['id' => $template->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      $template->asArray()
    );
  }

  function testItCanGetAllNewsletterTemplates() {
    $templates = NewsletterTemplate::count();

    $router = new NewsletterTemplates();
    $response = $router->getAll();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->count($templates);
  }

  function testItCanSaveANewTemplate() {
    $template_data = [
      'name' => 'Template #3',
      'body' => '{"key3": "value3"}',
    ];

    $router = new NewsletterTemplates();
    $response = $router->save($template_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      NewsletterTemplate::findOne($response->data['id'])->asArray()
    );
  }

  function testItCanSaveANewTemplateAssociatedWithANewsletter() {
    $template_data = [
      'newsletter_id' => 2,
      'name' => 'Template #3',
      'body' => '{"key3": "value3"}',
    ];

    $router = new NewsletterTemplates();
    $response = $router->save($template_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      NewsletterTemplate::findOne($response->data['id'])->asArray()
    );
  }

  function testItCanUpdateTemplateAssociatedWithANewsletter() {
    $template_data = [
      'newsletter_id' => '1',
      'name' => 'Template #2',
      'body' => '{"key3": "value3"}',
    ];

    $template_id = NewsletterTemplate::whereEqual('newsletter_id', 1)->findOne()->id;

    $router = new NewsletterTemplates();
    $response = $router->save($template_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $template_data['body'] = json_decode($template_data['body'], true);

    $normalize = function($array) {
      $result = [];
      foreach ($array as $key => $value) {
        if (in_array($key, ['newsletter_id', 'name', 'body'])) {
          $result[$key] = $value;
        }
      }
      return $result;
    };

    expect($normalize($response->data))->equals($template_data);
    $template = NewsletterTemplate::findOne($template_id)->asArray();
    expect($normalize($template))->equals($template_data);
  }

  function testItCanDeleteANewsletterTemplate() {
    $template = NewsletterTemplate::where('name', 'Template #2')->findOne();
    expect($template->deleted_at)->null();

    $router = new NewsletterTemplates();
    $response = $router->delete(/* missing id */);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])
      ->equals('This template does not exist.');

    $response = $router->delete(['id' => $template->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $deleted_template = NewsletterTemplate::findOne($template->id);
    expect($deleted_template)->false();
  }

  function _after() {
    NewsletterTemplate::deleteMany();
  }
}
