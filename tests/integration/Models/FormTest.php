<?php

namespace MailPoet\Test\Models;

use MailPoet\Models\Form;
use MailPoet\Settings\SettingsController;

class FormTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->form = Form::createOrUpdate([
      'name' => 'My Form',
    ]);
  }

  function testItCanBeCreated() {
    expect($this->form->id() > 0)->true();
    expect($this->form->getErrors())->false();
  }

  function testItCanBeGrouped() {
    $forms = Form::filter('groupBy', 'all')->findArray();
    expect($forms)->count(1);

    $forms = Form::filter('groupBy', 'trash')->findArray();
    expect($forms)->count(0);

    $this->form->trash();
    $forms = Form::filter('groupBy', 'trash')->findArray();
    expect($forms)->count(1);

    $forms = Form::filter('groupBy', 'all')->findArray();
    expect($forms)->count(0);

    $this->form->restore();
    $forms = Form::filter('groupBy', 'all')->findArray();
    expect($forms)->count(1);
  }

  function testItCanBeSearched() {
    $form = Form::filter('search', 'my F')->findOne();
    expect($form->name)->equals('My Form');
  }

  function testItHasACreatedAtOnCreation() {
    $form = Form::findOne($this->form->id);
    expect($form->created_at)->notNull();
  }

  function testItHasAnUpdatedAtOnCreation() {
    $form = Form::findOne($this->form->id);
    expect($form->updated_at)
      ->equals($form->created_at);
  }

  function testItUpdatesTheUpdatedAtOnUpdate() {
    $form = Form::findOne($this->form->id);
    $created_at = $form->created_at;

    sleep(1);

    $form->name = 'new name';
    $form->save();

    $updated_form = Form::findOne($form->id);
    expect($updated_form->created_at)->equals($created_at);
    $is_time_updated = (
      $updated_form->updated_at > $updated_form->created_at
    );
    expect($is_time_updated)->true();
  }

  function testItCanCreateOrUpdate() {
    $created_form = Form::createOrUpdate([
      'name' => 'Created Form',
    ]);
    expect($created_form->id > 0)->true();
    expect($created_form->getErrors())->false();

    $form = Form::findOne($created_form->id);
    expect($form->name)->equals('Created Form');

    $is_updated = Form::createOrUpdate([
      'id' => $created_form->id,
      'name' => 'Updated Form',
    ]);
    $form = Form::findOne($created_form->id);
    expect($form->name)->equals('Updated Form');
  }

  function testItCanProvideAFieldList() {
    $form = Form::createOrUpdate([
      'name' => 'My Form',
      'body' => [
        [
          'type' => 'text',
          'id' => 'email',
        ],
        [
          'type' => 'text',
          'id' => 2,
        ],
        [
          'type' => 'submit',
          'id' => 'submit',
        ],
      ],
    ]);
    expect($form->getFieldList())->equals(['email', 'cf_2']);
  }

  function testItUpdatesSuccessMessagesWhenConfirmationIsDisabled() {
    $default = Form::createOrUpdate([
      'name' => 'with default message',
      'settings' => ['success_message' => 'Check your inbox or spam folder to confirm your subscription.'],
    ]);
    $custom = Form::createOrUpdate([
      'name' => 'with custom message',
      'settings' => ['success_message' => 'Thanks for joining us!'],
    ]);
    $this->settings->set('signup_confirmation.enabled', '0');
    Form::updateSuccessMessages();
    $default = Form::findOne($default->id)->asArray();
    $custom = Form::findOne($custom->id)->asArray();
    expect($default['settings']['success_message'])->equals('You’ve been successfully subscribed to our newsletter!');
    expect($custom['settings']['success_message'])->equals('Thanks for joining us!');
  }

  function testItUpdatesSuccessMessagesWhenConfirmationIsEnabled() {
    $default = Form::createOrUpdate([
      'name' => 'with default message',
      'settings' => ['success_message' => 'Check your inbox or spam folder to confirm your subscription.'],
    ]);
    $custom = Form::createOrUpdate([
      'name' => 'with custom message',
      'settings' => ['success_message' => 'Thanks for joining us!'],
    ]);
    $this->settings->set('signup_confirmation.enabled', '1');
    Form::updateSuccessMessages();
    $default = Form::findOne($default->id)->asArray();
    $custom = Form::findOne($custom->id)->asArray();
    expect($default['settings']['success_message'])->equals('Check your inbox or spam folder to confirm your subscription.');
    expect($custom['settings']['success_message'])->equals('Thanks for joining us!');
  }

  function _after() {
    Form::deleteMany();
  }
}
