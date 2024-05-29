<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Form\FormMessageController;
use MailPoet\Subscription\Captcha\CaptchaConstants;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;

/**
 * @group frontend
 */
class SubscribeToMultipleListsCest {
  public function subscribeToMultipleLists(\AcceptanceTester $i) {
    //Step one - create form with three lists
    $segmentFactory = new Segment();
    $seg1 = 'Cats';
    $seg2 = 'Dogs';
    $seg3 = 'Fish';
    $segment1 = $segmentFactory->withName($seg1)->create();
    $segment2 = $segmentFactory->withName($seg2)->create();
    $segment3 = $segmentFactory->withName($seg3)->create();
    $formName = 'Multiple Lists Form';
    $formFactory = new Form();
    $form = $formFactory->withName($formName)->withSegments([$segment1, $segment2, $segment3])->create();
    $formId = $form->getId();

    $settings = new Settings();
    $settings
      ->withConfirmationEmailEnabled()
      ->withConfirmationEmailBody()
      ->withConfirmationEmailSubject('Subscribe to multiple test subject')
      ->withCaptchaType(CaptchaConstants::TYPE_DISABLED);

    $formFactory->withDefaultSuccessMessage();

    $i->havePostInDatabase([
      'post_author' => 1,
      'post_type' => 'page',
      'post_name' => 'form-test',
      'post_title' => 'Form Test',
      'post_content' => '
        Regular form:
          [mailpoet_form id="' . $formId . '"]
        Iframe form:
          <iframe class="mailpoet_form_iframe" id="mailpoet_form_iframe" tabindex="0" src="http://test.local?mailpoet_form_iframe=1" width="100%" height="100%" frameborder="0" marginwidth="0" marginheight="0" scrolling="no"></iframe>
      ',
      'post_status' => 'publish',
    ]);
  
    $i->wantTo('Subscribe to the form with multiple lists attached');
    $i->amOnPage('/form-test');
    $i->waitForElement('[data-automation-id="form_email"]');
    $i->fillField('[data-automation-id="form_email"]', 'subscriber@example.com');
    $i->click('[data-automation-id="subscribe-submit-button"]');
    $messageController = ContainerWrapper::getInstance()->get(FormMessageController::class);
    $i->waitForText($messageController->getDefaultSuccessMessage(), 30, '.mailpoet_validate_success');
    $i->seeNoJSErrors();

    $i->wantTo('Confirm subscription to subscribed form');
    $i->amOnMailboxAppPage();
    $i->click(Locator::contains('span.subject', 'Subscribe to multiple test subject'));
    $i->switchToIframe('#preview-html');
    $i->click('I confirm my subscription!');
    $i->switchToNextTab();
    $i->see('You have subscribed');
    $i->seeNoJSErrors();

    $i->wantTo('Check if the three lists are present for the subscribed user');
    $i->amOnUrl(\AcceptanceTester::WP_URL);
    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->waitForText($seg1);
    $i->waitForText($seg2);
    $i->waitForText($seg3);
  }
}
