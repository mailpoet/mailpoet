<?php declare(strict_types = 1);

namespace MailPoet\Statistics;

use MailPoet\Entities\NewsletterEntity;
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
    $this->gaCampaign = 'Spring email';
    $this->link = add_query_arg(['foo' => 'bar', 'baz' => 'xyz'], 'https://www.mailpoet.com/');
    $this->renderedNewsletter = [
      'html' => '<p><a href="' . $this->link . '">Click here</a>. <a href="http://somehost.com/fff/?abc=123&email=[subscriber:email]">Do not process this</a> [link:some_link_shortcode]</p>',
      'text' => '[Click here](' . $this->link . '). [Do not process this](http://somehost.com/fff/?abc=123&email=[subscriber:email]) [link:some_link_shortcode]',
    ];
    $this->newsletter = (new NewsletterFactory())->withGaCampaign($this->gaCampaign)->create();
  }

  public function testItConditionallyAppliesGATracking() {
    // No process (empty GA campaign)
    $newsletter = (new NewsletterFactory())->create();
    $result = $this->tracking->applyGATracking($this->renderedNewsletter, $newsletter, $this->internalHost);
    expect($result)->equals($this->renderedNewsletter);

    // Process (filled GA campaign)
    $result = $this->tracking->applyGATracking($this->renderedNewsletter, $this->newsletter, $this->internalHost);
    expect($result)->notEquals($this->renderedNewsletter);
  }

  public function testItGetsGACampaignFromParentNewsletterForPostNotifications() {
    $notificationHistory = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY)
      ->withParent($this->newsletter)
      ->create();

    $result = $this->tracking->applyGATracking($this->renderedNewsletter, $notificationHistory, $this->internalHost);
    expect($result)->notEquals($this->renderedNewsletter);
  }

  public function testItCanAddGAParamsToLinks() {
    $result = $this->tracking->applyGATracking($this->renderedNewsletter, $this->newsletter, $this->internalHost);
    expect($result['text'])->stringContainsString('utm_campaign=' . urlencode($this->gaCampaign));
    expect($result['html'])->stringContainsString('utm_campaign=' . urlencode($this->gaCampaign));
  }

  public function testItKeepsShorcodes() {
    $result = $this->tracking->applyGATracking($this->renderedNewsletter, $this->newsletter, $this->internalHost);
    expect($result['text'])->stringContainsString('email=[subscriber:email]');
    expect($result['html'])->stringContainsString('email=[subscriber:email]');
  }
}
