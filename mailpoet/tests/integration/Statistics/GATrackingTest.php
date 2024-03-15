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
}
