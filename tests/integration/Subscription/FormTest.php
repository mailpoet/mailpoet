<?php

namespace MailPoet\Test\Subscription;

use Codeception\Stub;
use MailPoet\API\JSON\API;
use MailPoet\API\JSON\ErrorResponse;
use MailPoet\API\JSON\Response;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Models\Form as FormModel;
use MailPoet\Models\Segment as SegmentModel;
use MailPoet\Models\Subscriber as SubscriberModel;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Subscription\Form;
use MailPoet\Util\Security;
use MailPoet\Util\Url as UrlHelper;

class FormTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->settings->set('sender', [
      'name' => 'John Doe',
      'address' => 'john.doe@example.com',
    ]);
    $this->testEmail = 'test@example.com';
    $this->segment = SegmentModel::createOrUpdate(
      [
        'name' => 'Test segment',
      ]
    );
    $this->form = FormModel::createOrUpdate(
      [
        'name' => 'Test form',
        'body' => [
          [
            'type' => 'text',
            'id' => 'email',
          ],
        ],
        'settings' => [
          'segments' => [$this->segment->id],
        ],
      ]
    );
    $obfuscator = new FieldNameObfuscator();
    $obfuscatedEmail = $obfuscator->obfuscate('email');
    $this->request_data = [
      'action' => 'mailpoet_subscription_form',
      'data' => [
        'form_id' => $this->form->id,
        $obfuscatedEmail => $this->testEmail,
      ],
      'token' => Security::generateToken(),
      'api_version' => 'v1',
      'endpoint' => 'subscribers',
      'mailpoet_method' => 'subscribe',
    ];
    $this->post = wp_insert_post(
      [
        'post_title' => 'Sample Post',
        'post_content' => 'contents',
        'post_status' => 'publish',
      ]
    );
    $this->settings->set('signup_confirmation.enabled', false);
  }

  function testItSubscribesAndRedirectsBackWithSuccessResponse() {
    $url_helper = Stub::make(UrlHelper::class, [
      'redirectBack' => function($params) {
        return $params;
      },
    ], $this);
    $form_controller = new Form(ContainerWrapper::getInstance()->get(API::class), $url_helper);
    $result = $form_controller->onSubmit($this->request_data);
    expect(SubscriberModel::findOne($this->testEmail))->notEmpty();
    expect($result['mailpoet_success'])->equals($this->form->id);
    expect($result['mailpoet_error'])->null();
  }

  function testItSubscribesAndRedirectsToCustomUrlWithSuccessResponse() {
    // update form with a redirect setting
    $form = $this->form;
    $form_settings = unserialize($form->settings);
    $form_settings['on_success'] = 'page';
    $form_settings['success_page'] = $this->post;
    $form->settings = serialize($form_settings);
    $form->save();
    $url_helper = Stub::make(UrlHelper::class, [
      'redirectTo' => function($params) {
        return $params;
      },
      'redirectBack' => function($params) {
        return $params;
      },
    ], $this);
    $form_controller = new Form(ContainerWrapper::getInstance()->get(API::class), $url_helper);
    $result = $form_controller->onSubmit($this->request_data);
    expect(SubscriberModel::findOne($this->testEmail))->notEmpty();
    expect($result)->regExp('/http.*?sample-post/i');
  }

  function testItDoesNotSubscribeAndRedirectsBackWithErrorResponse() {
    // clear subscriber email so that subscription fails
    $request_data = $this->request_data;
    $request_data['data']['email'] = false;
    $url_helper = Stub::make(UrlHelper::class, [
      'redirectBack' => function($params) {
        return $params;
      },
    ], $this);
    $form_controller = new Form(ContainerWrapper::getInstance()->get(API::class), $url_helper);
    $result = $form_controller->onSubmit($request_data);
    expect(SubscriberModel::findMany())->isEmpty();
    expect($result['mailpoet_error'])->equals($this->form->id);
    expect($result['mailpoet_success'])->null();
  }

  function testItDoesNotSubscribeAndRedirectsToRedirectUrlIfPresent() {
    $redirect_url = 'http://test/';
    $url_helper = Stub::make(UrlHelper::class, [
      'redirectTo' => function($params) {
        return $params;
      },
    ], $this);
    $api = Stub::makeEmpty(API::class, [
      'processRoute' => function() use ($redirect_url) {
        return new ErrorResponse([], ['redirect_url' => $redirect_url], Response::STATUS_BAD_REQUEST);
      },
    ], $this);
    $form_controller = new Form($api, $url_helper);
    $result = $form_controller->onSubmit($this->request_data);
    expect($result)->equals($redirect_url);
  }

  function _after() {
    wp_delete_post($this->post);
    \ORM::raw_execute('TRUNCATE ' . SegmentModel::$_table);
    \ORM::raw_execute('TRUNCATE ' . FormModel::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberModel::$_table);
    $this->di_container->get(SettingsRepository::class)->truncate();
  }
}
