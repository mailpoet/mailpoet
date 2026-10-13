<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Captcha\CaptchaConstants;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Test\DataFactories\CustomField;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;

/**
 * @group frontend
 */
class SubscriptionFormErrorMessageCest {
  public function showsCustomFieldNameAsTextInErrorMessage(\AcceptanceTester $i) {
    (new Settings())->withCaptchaType(CaptchaConstants::TYPE_DISABLED);
    $segment = (new Segment())->withName('Error message list')->create();
    $customFieldName = 'Nickname <b>bold</b>';
    $customField = (new CustomField())
      ->withName($customFieldName)
      ->withType(CustomFieldEntity::TYPE_TEXT)
      ->withParams(['required' => '1'])
      ->create();
    $form = (new Form())
      ->withName('Error message form')
      ->withSegments([$segment])
      ->withCustomField($customField)
      ->create();
    $i->havePostInDatabase([
      'post_author' => 1,
      'post_type' => 'page',
      'post_name' => 'form-error-message-test',
      'post_title' => 'Form Error Message Test',
      'post_content' => '[mailpoet_form id="' . $form->getId() . '"]',
      'post_status' => 'publish',
    ]);

    $i->wantTo('Submit a value the server treats as missing and see the field name as text');
    $i->amOnPage('/form-error-message-test');
    $i->waitForElement('[data-automation-id="form_email"]');
    $i->fillField('[data-automation-id="form_email"]', 'error-message@example.com');
    $i->fillField('input[name="data[cf_' . $customField->getId() . ']"]', '0');
    $i->click('[data-automation-id="subscribe-submit-button"]');
    $i->waitForText('Missing value for custom field "' . $customFieldName . '"', 10, '.mailpoet_validate_error');
    $i->dontSeeElement('.mailpoet_validate_error b');
    $i->seeNoJSErrors();
  }
}
