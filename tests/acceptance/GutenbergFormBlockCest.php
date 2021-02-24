<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Settings;

class GutenbergFormBlockCest {

  const CONFIRMATION_MESSAGE_TIMEOUT = 20;

  /** @var string */
  private $subscriberEmail;

  /** @var string */
  private $firstName;

  /** @var string */
  private $lastName;

  public function __construct() {
    $this->subscriberEmail = 'test-form@example.com';
    $this->firstName = 'First Name';
    $this->lastName = 'Last Name';
  }

  public function _before(\AcceptanceTester $i) {
    $settings = new Settings();
    $settings
      ->withConfirmationEmailSubject()
      ->withConfirmationEmailBody()
      ->withConfirmationEmailEnabled();
  }

  public function subscriptionGutenbergBlock(\AcceptanceTester $i): void {
    $formFactory = new Form();
    $formId = (int)$formFactory->withName('Acceptance Test Block Form')->create()->id;
    $postId = $this->createPost($i, $formId);

    $i->wantTo('Add Gutenberg form block to the post');
    $i->amOnPage("/?p={$postId}");
    $i->waitForElementVisible('[data-automation-id="form_email"]');
    $i->fillField('[data-automation-id="form_email"]', $this->subscriberEmail);
    $i->click('.mailpoet_submit');
    $i->waitForText('Check your inbox or spam folder to confirm your subscription.', self::CONFIRMATION_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
    $i->seeNoJSErrors();
  }

  public function subscriptionGutenbergBlockWithName(\AcceptanceTester $i): void {
    $formFactory = new Form();
    $formId = (int)$formFactory
      ->withName('Acceptance Test Block Form')
      ->withLastName()
      ->withFirstName()
      ->create()->id;
    $postId = $this->createPost($i, $formId);

    $i->wantTo('Add Gutenberg form block to the post');
    $i->amOnPage("/?p={$postId}");
    $i->waitForElementVisible('[data-automation-id="form_email"]');
    $i->fillField('[data-automation-id="form_email"]', $this->subscriberEmail);
    $i->fillField('[data-automation-id="form_first_name"]', $this->firstName);
    $i->fillField('[data-automation-id="form_last_name"]', $this->lastName);
    $i->click('.mailpoet_submit');
    $i->waitForText('Check your inbox or spam folder to confirm your subscription.', self::CONFIRMATION_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
    $i->seeNoJSErrors();
  }

  private function createPost(\AcceptanceTester $i, int $formId): int {
    return $i->havePostInDatabase([
      'post_author' => 1,
      'post_type' => 'page',
      'post_name' => 'form-test',
      'post_title' => 'My form',
      'post_content' => '
        <!-- wp:mailpoet/subscription-form-block {"formId":' . $formId . '} /-->
      ',
      'post_status' => 'publish',
    ]);
  }
}
