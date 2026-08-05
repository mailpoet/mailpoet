<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;

/**
 * @group frontend
 */
class TrackingOptOutLinksCest {
  private const PLAIN_LINK = 'Post link';
  private const UNSUBSCRIBE_LINK = 'Unsubscribe link';

  public function _before() {
    $settings = new Settings();
    $settings->withTrackingEnabled();
    $settings->withCronTriggerMethod('Action Scheduler');
  }

  public function itDoesNotSendTrackedLinksToSubscriberWithoutTrackingConsent(\AcceptanceTester $i) {
    $i->wantTo('Verify a subscriber who opted out of tracking receives plain links, not click-tracking redirects');

    $subject = 'Untracked links for opted-out subscriber';
    $segment = (new Segment())->withName('Tracking opt-out send list')->create();
    (new Subscriber())
      ->withEmail('optout-links@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withTrackingConsent(SubscriberEntity::TRACKING_CONSENT_DENIED)
      ->withSegments([$segment])
      ->create();

    // Spelled out rather than reusing a fixture, so the two links asserted on
    // below are visible here: one plain external URL, one link shortcode.
    $newsletter = (new Newsletter())
      ->withSubject($subject)
      ->withBody($this->bodyWithBothLinkKinds())
      ->create();

    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/send/' . $newsletter->getId());
    $i->waitForElement('[data-automation-id="newsletter_send_form"]');
    $i->selectOptionInSelect2($segment->getName());
    $i->click('Send');
    $i->waitForEmailSendingOrSent();
    $i->triggerMailPoetActionScheduler();

    $i->amOnMailboxAppPage();
    $i->checkEmailWasReceived($subject);
    $i->click(Locator::contains('span.subject', $subject));
    $i->switchToIframe('#preview-html');

    $i->wantTo('See the external link keep its real destination instead of a tracking redirect');
    $plainLink = ['xpath' => '//a[normalize-space(text())="' . self::PLAIN_LINK . '"]'];
    $i->assertAttributeNotContains($plainLink, 'href', 'endpoint=track');
    $i->assertAttributeContains($plainLink, 'href', 'example.com');

    $i->wantTo('See the unsubscribe shortcode resolved to a working URL, not left as raw shortcode text');
    $unsubscribeLink = ['xpath' => '//a[normalize-space(text())="' . self::UNSUBSCRIBE_LINK . '"]'];
    $i->assertAttributeNotContains($unsubscribeLink, 'href', 'endpoint=track');
    $i->assertAttributeNotContains($unsubscribeLink, 'href', '[link:');
    $i->assertAttributeContains($unsubscribeLink, 'href', 'action=confirm_unsubscribe');
  }

  /**
   * @return array<string, mixed>
   */
  private function bodyWithBothLinkKinds(): array {
    $text = sprintf(
      '<a href="http://example.com/8307-plain-link">%s</a> <a href="[link:subscription_unsubscribe_url]">%s</a>',
      self::PLAIN_LINK,
      self::UNSUBSCRIBE_LINK
    );

    return [
      'content' => [
        'type' => 'container',
        'orientation' => 'vertical',
        'styles' => ['block' => []],
        'blocks' => [
          [
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => ['block' => []],
            'blocks' => [
              [
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => ['block' => []],
                'blocks' => [
                  [
                    'type' => 'text',
                    'text' => $text,
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }
}
