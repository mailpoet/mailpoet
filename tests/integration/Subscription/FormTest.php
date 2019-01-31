<?php
namespace MailPoet\Test\Subscription;

use AspectMock\Test as Mock;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Models\Form as FormModel;
use MailPoet\Models\Segment as SegmentModel;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber as SubscriberModel;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Form;
use MailPoet\Util\Security;

class FormTest extends \MailPoetTest {

  /** @var Form */
  private $form_controller;

  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    $this->settings = new SettingsController();
    $this->settings->set('sender', array(
      'name' => 'John Doe',
      'address' => 'john.doe@example.com'
    ));
    $this->testEmail = 'test@example.com';
    $this->segment = SegmentModel::createOrUpdate(
      array(
        'name' => 'Test segment'
      )
    );
    $this->form = FormModel::createOrUpdate(
      array(
        'name' => 'Test form',
        'body' => array(
          array(
            'type' => 'text',
            'id' => 'email'
          )
        ),
        'settings' => array(
          'segments' => array($this->segment->id)
        )
      )
    );
    $obfuscator = new FieldNameObfuscator();
    $obfuscatedEmail = $obfuscator->obfuscate('email');
    $this->request_data = array(
      'action' => 'mailpoet_subscription_form',
      'data' => array(
        'form_id' => $this->form->id,
        $obfuscatedEmail => $this->testEmail
      ),
      'token' => Security::generateToken(),
      'api_version' => 'v1',
      'endpoint' => 'subscribers',
      'mailpoet_method' => 'subscribe'
    );
    $this->post = wp_insert_post(
      array(
        'post_title' => 'Sample Post',
        'post_content' => 'contents',
        'post_status' => 'publish',
      )
    );
    $this->settings->set('signup_confirmation.enabled', false);
    $this->form_controller = ContainerWrapper::getInstance()->get(Form::class);
  }

  function testItSubscribesAndRedirectsBackWithSuccessResponse() {
    $mock = Mock::double('MailPoet\Util\Url', [
      'redirectBack' => function($params) {
        return $params;
      }
    ]);
    $result = $this->form_controller->onSubmit($this->request_data);
    expect(SubscriberModel::findOne($this->testEmail))->notEmpty();
    $mock->verifyInvoked('redirectBack');
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
    $mock = Mock::double('MailPoet\Util\Url', [
      'redirectTo' => function($params) {
        return $params;
      },
      'redirectBack' => function($params) {
        return $params;
      }
    ]);
    $result = $this->form_controller->onSubmit($this->request_data);
    expect(SubscriberModel::findOne($this->testEmail))->notEmpty();
    $mock->verifyInvoked('redirectTo');
    expect($result)->regExp('/http.*?sample-post/i');
  }

  function testItDoesNotSubscribeAndRedirectsBackWithErrorResponse() {
    // clear subscriber email so that subscription fails
    $request_data = $this->request_data;
    $request_data['data']['email'] = false;
    $mock = Mock::double('MailPoet\Util\Url', [
      'redirectBack' => function($params) {
        return $params;
      }
    ]);
    $result = $this->form_controller->onSubmit($request_data);
    expect(SubscriberModel::findMany())->isEmpty();
    $mock->verifyInvoked('redirectBack');
    expect($result['mailpoet_error'])->equals($this->form->id);
    expect($result['mailpoet_success'])->null();
  }

  function _after() {
    Mock::clean();
    wp_delete_post($this->post);
    \ORM::raw_execute('TRUNCATE ' . SegmentModel::$_table);
    \ORM::raw_execute('TRUNCATE ' . FormModel::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberModel::$_table);
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}
