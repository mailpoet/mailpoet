<?php declare(strict_types = 1);

namespace MailPoet\Statistics;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;

class GATrackingTest extends \MailPoetTest {

  /** @var string */
  private $internalHost;

  /** @var string */
  private $gaCampaign;

  /** @var string */
  private $link;

  /** @var string[] */
  private $renderedNewsletter;

  /** @var GATracking */
  private $tracking;

  /** @var NewsletterEntity */
  private $newsletter;

  public function _before() {
    $this->tracking = $this->diContainer->get(GATracking::class);
    $this->internalHost = 'newsletters.mailpoet.com';
    $this->gaCampaign = 'SpringEmail';
    $this->link = add_query_arg(['foo' => 'bar', 'baz' => 'xyz'], 'https://www.mailpoet.com/');
    $this->renderedNewsletter = [
      'html' => '<p><a href="' . $this->link . '">Click here</a>. <a href="http://somehost.com/fff/?abc=123&email=[subscriber:email]">Do not process this</a> [link:some_link_shortcode]</p>',
      'text' => '[Click here](' . $this->link . '). [Do not process this](http://somehost.com/fff/?abc=123&email=[subscriber:email]) [link:some_link_shortcode]',
    ];
    $this->newsletter = (new NewsletterFactory())->withGaCampaign($this->gaCampaign)->create();
  }

  public function testItDoesNotSetGACampaignWhenTrackingIsDisabled() {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set('tracking.level', TrackingConfig::LEVEL_BASIC);
    $result = $this->tracking->applyGATracking($this->renderedNewsletter, $this->newsletter, $this->internalHost);
    verify($result)->equals($this->renderedNewsletter);
    verify($result['text'])->stringNotContainsString(add_query_arg([
      'utm_source' => 'mailpoet',
      'utm_medium' => 'email',
      'utm_source_platform' => 'mailpoet',
      'utm_campaign' => $this->gaCampaign,
    ], $this->link));

    $settings->set('tracking.level', TrackingConfig::LEVEL_PARTIAL);
    $result = $this->tracking->applyGATracking($this->renderedNewsletter, $this->newsletter, $this->internalHost);
    verify($result['text'])->stringContainsString(add_query_arg([
      'utm_source' => 'mailpoet',
      'utm_medium' => 'email',
      'utm_source_platform' => 'mailpoet',
      'utm_campaign' => $this->gaCampaign,
    ], $this->link));

    $settings->set('tracking.level', TrackingConfig::LEVEL_FULL);
    $result = $this->tracking->applyGATracking($this->renderedNewsletter, $this->newsletter, $this->internalHost);
    verify($result['text'])->stringContainsString(add_query_arg([
      'utm_source' => 'mailpoet',
      'utm_medium' => 'email',
      'utm_source_platform' => 'mailpoet',
      'utm_campaign' => $this->gaCampaign,
    ], $this->link));
  }

  public function testItGetsGACampaignFromParentNewsletterForPostNotifications() {
    $notificationHistory = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY)
      ->withParent($this->newsletter)
      ->create();

    $result = $this->tracking->applyGATracking($this->renderedNewsletter, $notificationHistory, $this->internalHost);
    verify($result)->notEquals($this->renderedNewsletter);
  }

  public function testItCanAddGAParamsToLinks() {
    $result = $this->tracking->applyGATracking($this->renderedNewsletter, $this->newsletter, $this->internalHost);
    verify($result['text'])->stringContainsString('utm_campaign=' . urlencode($this->gaCampaign));
    verify($result['html'])->stringContainsString('utm_campaign=' . urlencode($this->gaCampaign));
  }

  public function testItKeepsShorcodes() {
    $result = $this->tracking->applyGATracking($this->renderedNewsletter, $this->newsletter, $this->internalHost);
    verify($result['text'])->stringContainsString('email=[subscriber:email]');
    verify($result['html'])->stringContainsString('email=[subscriber:email]');
  }

  public function testItDoesntBreakSpecialHtmlComments() {
    $this->renderedNewsletter = [
      'html' => '<p><!--[if !mso]><!-- -->Test</p>',
      'text' => 'Test',
    ];
    $result = $this->tracking->applyGATracking($this->renderedNewsletter, $this->newsletter, $this->internalHost);
    verify($result['html'])->stringContainsString('<!--[if !mso]><!-- -->');
  }

  public function testItDoesNotOverwriteExistingParameters() {
    $link = add_query_arg(
      [
        'utm_source' => 'another_source',
        'utm_medium' => 'another_medium',
      ],
      $this->link
    );
    $renderedNewsletter = [
      'html' => '<p><a href="' . $link . '">Click here</a></p>',
      'text' => '[Click here](' . $link . ')',
    ];
    $result = $this->tracking->applyGATracking($renderedNewsletter, $this->newsletter, $this->internalHost);
    verify($result['text'])->stringNotContainsString('utm_source=mailpoet');
    verify($result['html'])->stringContainsString('utm_source=another_source');
    verify($result['text'])->stringNotContainsString('utm_medium=email');
    verify($result['html'])->stringContainsString('utm_medium=another_medium');
  }

  public function testItPreservesShortcodesInInternalLinks() {
    $internalLink = 'http://newsletters.mailpoet.com/?email=[subscriber:email]&name=[subscriber:firstname|default:reader]';
    $renderedNewsletter = [
      'html' => '<p><a href="' . $internalLink . '">Click here</a></p>',
      'text' => '[Click here](' . $internalLink . ')',
    ];
    $result = $this->tracking->applyGATracking($renderedNewsletter, $this->newsletter, $this->internalHost);
    // Should contain GA tracking parameters
    verify($result['text'])->stringContainsString('utm_source=mailpoet');
    verify($result['html'])->stringContainsString('utm_source=mailpoet');
    // Should preserve shortcodes without URL encoding
    verify($result['text'])->stringContainsString('email=[subscriber:email]');
    verify($result['html'])->stringContainsString('email=[subscriber:email]');
    verify($result['text'])->stringContainsString('name=[subscriber:firstname|default:reader]');
    verify($result['html'])->stringContainsString('name=[subscriber:firstname|default:reader]');
    // Should NOT contain URL-encoded shortcodes
    verify($result['text'])->stringNotContainsString('email=%5Bsubscriber%3Aemail%5D');
    verify($result['html'])->stringNotContainsString('email=%5Bsubscriber%3Aemail%5D');
  }

  public function testItPreservesComplexShortcodesInInternalLinks() {
    // Test with multiple complex shortcodes including various special characters
    $internalLink = 'http://newsletters.mailpoet.com/?user=[subscriber:email]&first=[subscriber:firstname|default:Guest User]&last=[subscriber:lastname]&custom=[subscriber:cf_1:custom field|default:N/A]';
    $renderedNewsletter = [
      'html' => '<p><a href="' . $internalLink . '">Click here</a></p>',
      'text' => '[Click here](' . $internalLink . ')',
    ];
    $result = $this->tracking->applyGATracking($renderedNewsletter, $this->newsletter, $this->internalHost);
    // Should contain GA tracking parameters
    verify($result['text'])->stringContainsString('utm_source=mailpoet');
    verify($result['html'])->stringContainsString('utm_source=mailpoet');
    verify($result['text'])->stringContainsString('utm_campaign=' . urlencode($this->gaCampaign));
    verify($result['html'])->stringContainsString('utm_campaign=' . urlencode($this->gaCampaign));
    // Should preserve all shortcodes exactly as they were
    verify($result['text'])->stringContainsString('user=[subscriber:email]');
    verify($result['html'])->stringContainsString('user=[subscriber:email]');
    verify($result['text'])->stringContainsString('first=[subscriber:firstname|default:Guest User]');
    verify($result['html'])->stringContainsString('first=[subscriber:firstname|default:Guest User]');
    verify($result['text'])->stringContainsString('last=[subscriber:lastname]');
    verify($result['html'])->stringContainsString('last=[subscriber:lastname]');
    verify($result['text'])->stringContainsString('custom=[subscriber:cf_1:custom field|default:N/A]');
    verify($result['html'])->stringContainsString('custom=[subscriber:cf_1:custom field|default:N/A]');
  }
}
