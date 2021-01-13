<?php

namespace MailPoet\Test\Models;

use MailPoet\Models\Form;
use MailPoet\Settings\SettingsController;

class FormTest extends \MailPoetTest {
  public $form;
  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->form = Form::createOrUpdate([
      'name' => 'My Form',
    ]);
  }

  public function testItCanBeCreated() {
    expect($this->form->id() > 0)->true();
    expect($this->form->getErrors())->false();
  }

  public function testItCanBeGrouped() {
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

  public function testItCanBeSearched() {
    $form = Form::filter('search', 'my F')->findOne();
    assert($form instanceof Form);
    expect($form->name)->equals('My Form');
  }

  public function testItHasACreatedAtOnCreation() {
    $form = Form::findOne($this->form->id);
    assert($form instanceof Form);
    expect($form->createdAt)->notNull();
  }

  public function testItHasAnUpdatedAtOnCreation() {
    $form = Form::findOne($this->form->id);
    assert($form instanceof Form);
    expect($form->updatedAt)->equals($form->createdAt);
  }

  public function testItUpdatesTheUpdatedAtOnUpdate() {
    $form = Form::findOne($this->form->id);
    assert($form instanceof Form);
    $createdAt = $form->createdAt;

    sleep(1);

    $form->name = 'new name';
    $form->save();

    $updatedForm = Form::findOne($form->id);
    assert($updatedForm instanceof Form);
    expect($updatedForm->createdAt)->equals($createdAt);
    $isTimeUpdated = (
      $updatedForm->updatedAt > $updatedForm->createdAt
    );
    expect($isTimeUpdated)->true();
  }

  public function testItCanCreateOrUpdate() {
    $createdForm = Form::createOrUpdate([
      'name' => 'Created Form',
    ]);
    expect($createdForm->id > 0)->true();
    expect($createdForm->getErrors())->false();

    $form = Form::findOne($createdForm->id);
    assert($form instanceof Form);
    expect($form->name)->equals('Created Form');

    $isUpdated = Form::createOrUpdate([
      'id' => $createdForm->id,
      'name' => 'Updated Form',
    ]);
    $form = Form::findOne($createdForm->id);
    assert($form instanceof Form);
    expect($form->name)->equals('Updated Form');
  }

  public function testItCanProvideAFieldList() {
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
          'type' => 'columns',
          'body' => [
            [
              'type' => 'column',
              'body' => [
                [
                  'type' => 'text',
                  'id' => 3,
                ],
              ],
            ],
            [
              'type' => 'column',
              'body' => [
                [
                  'type' => 'divider',
                  'id' => 'divider',
                ],
              ],
            ],
          ],
        ],
        [
          'type' => 'submit',
          'id' => 'submit',
        ],
      ],
    ]);
    expect($form->getFieldList())->equals(['email', 'cf_2', 'cf_3']);
  }

  public function testItUpdatesSuccessMessagesWhenConfirmationIsDisabled() {
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
    $default = Form::findOne($default->id);
    $custom = Form::findOne($custom->id);
    assert($default instanceof Form);
    assert($custom instanceof Form);
    $default = $default->asArray();
    $custom = $custom->asArray();
    expect($default['settings']['success_message'])->equals('You’ve been successfully subscribed to our newsletter!');
    expect($custom['settings']['success_message'])->equals('Thanks for joining us!');
  }

  public function testItUpdatesSuccessMessagesWhenConfirmationIsEnabled() {
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
    $default = Form::findOne($default->id);
    $custom = Form::findOne($custom->id);
    assert($default instanceof Form);
    assert($custom instanceof Form);
    $default = $default->asArray();
    $custom = $custom->asArray();
    expect($default['settings']['success_message'])->equals('Check your inbox or spam folder to confirm your subscription.');
    expect($custom['settings']['success_message'])->equals('Thanks for joining us!');
  }

  public function _after() {
    Form::deleteMany();
  }
}
