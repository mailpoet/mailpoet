<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter;

use Codeception\Util\Stub;
use MailPoet\Newsletter\NewsletterValidator;
use MailPoet\Services\Bridge;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Test\DataFactories\Newsletter;

class NewsletterValidatorTest extends \MailPoetTest {
  /** @var NewsletterValidator */
  private $newsletterValidator;

  public function _before() {
    parent::_before();
    $this->newsletterValidator = $this->diContainer->get(NewsletterValidator::class);
  }

  public function testUnsubscribeFooterIsNotRequiredIfNotUsingMSS() {
      $newsletter = (new Newsletter())->loadBodyFrom('newsletterWithTextNoFooter.json')->create();
      $validationError = $this->newsletterValidator->validate($newsletter);
      expect($validationError)->null();
  }

  public function testUnsubscribeFooterRequiredIfUsingMSS() {
    $newsletter = (new Newsletter())->loadBodyFrom('newsletterWithTextNoFooter.json')->create();
    $bridge = Stub::make(Bridge::class, ['isMailpoetSendingServiceEnabled' => true]);
    $validator = $this->getServiceWithOverrides(NewsletterValidator::class, ['bridge' => $bridge]);
    $validationError = $validator->validate($newsletter);
    expect($validationError)->equals('All emails must include an "Unsubscribe" link. Add a footer widget to your email to continue.');
  }

  public function testItRequiresBodyContent() {
    $newsletter = (new Newsletter())->withBody('')->create();
    $validationError = $this->newsletterValidator->validate($newsletter);
    expect($validationError)->equals('Poet, please add prose to your masterpiece before you send it to your followers.');
  }

  public function testItRequiresContentBlocks() {
    $newsletter = (new Newsletter())->withBody(['content' => ['type' => 'container', 'columnLayout' => false, 'orientation' => 'vertical', 'blocks' => []]])->create();
    $validationError = $this->newsletterValidator->validate($newsletter);
    expect($validationError)->equals('Poet, please add prose to your masterpiece before you send it to your followers.');
  }

  public function testItIsValidWithAContentBlock() {
    $newsletter = (new Newsletter())->withBody(['content' => ['type' => 'container', 'columnLayout' => false, 'orientation' => 'vertical', 'blocks' => [
      [
        'type' => 'text',
        'text' => 'Some text',
      ],
    ]]])->create();
    $validationError = $this->newsletterValidator->validate($newsletter);
    expect($validationError)->null();
  }

  public function testItRequiresReengagementShortcodes() {
    $newsletter = (new Newsletter())->withReengagementType()->withDefaultBody()->create();
    $validationError = $this->newsletterValidator->validate($newsletter);
    expect($validationError)->equals('A re-engagement email must include a link with [link:subscription_re_engage_url] shortcode.');
  }

  public function testReengagementNewsletterIsValidWithRequiredShortcode() {
    $newsletter = (new Newsletter())->withReengagementType()->withBody([
      'content' => [
        'blocks' => [
          [
            'type' => 'text',
            'text' => '[link:subscription_re_engage_url]',
          ],
        ],
      ],
    ])->create();
    $validationError = $this->newsletterValidator->validate($newsletter);
    expect($validationError)->null();
  }

  public function testItRequiresTrackingForReengagementEmails() {
    $newsletter = (new Newsletter())->withReengagementType()->withBody([
      'content' => [
        'blocks' => [
          [
            'type' => 'text',
            'text' => '[link:subscription_re_engage_url]',
          ],
        ],
      ],
    ])->create();
    $validator = $this->getServiceWithOverrides(NewsletterValidator::class, [
      'trackingConfig' => Stub::make(TrackingConfig::class, ['isEmailTrackingEnabled' => false]),
    ]);
    $validationError = $validator->validate($newsletter);
    expect($validationError)->equals('Re-engagement emails are disabled because open and click tracking is disabled in MailPoet → Settings → Advanced.');
  }

  public function testAlcEmailFailsValidationWithoutAlcBlock() {
    $newsletter = (new Newsletter())->withDefaultBody()->withPostNotificationsType()->create();
    $validationError = $this->newsletterValidator->validate($newsletter);
    expect($validationError)->equals('Please add an “Automatic Latest Content” widget to the email from the right sidebar.');
  }

  public function testAlcEmailPassesWithAlcBlock() {
    $newsletter = (new Newsletter())->loadBodyFrom('newsletterWithALC.json')->withPostNotificationsType()->create();
    $validationError = $this->newsletterValidator->validate($newsletter);
    expect($validationError)->null();
  }
}
