<?php

namespace MailPoet\Statistics;

use MailPoet\Models\Newsletter;
use MailPoetVendor\Idiorm\ORM;

class GATrackingTest extends \MailPoetTest {

  /** @var string */
  private $internalHost;

  /** @var string */
  private $gaCampaign;

  /** @var string */
  private $link;

  /** @var string[] */
  private $renderedNewsletter;

  public function _before() {
    $this->internalHost = 'newsletters.mailpoet.com';
    $this->gaCampaign = 'Spring email';
    $this->link = add_query_arg(['foo' => 'bar', 'baz' => 'xyz'], 'http://www.mailpoet.com/');
    $this->renderedNewsletter = [
      'html' => '<p><a href="' . $this->link . '">Click here</a>. <a href="http://somehost.com/fff/?abc=123&email=[subscriber:email]">Do not process this</a> [link:some_link_shortcode]</p>',
      'text' => '[Click here](' . $this->link . '). [Do not process this](http://somehost.com/fff/?abc=123&email=[subscriber:email]) [link:some_link_shortcode]',
    ];
  }

  public function testItConditionallyAppliesGATracking() {
    // No process (empty GA campaign)
    $newsletter = Newsletter::createOrUpdate(['id' => 123]);
    $tracking = new GATracking();
    $result = $tracking->applyGATracking($this->renderedNewsletter, $newsletter, $this->internalHost);
    expect($result)->equals($this->renderedNewsletter);
    // Process (filled GA campaign)
    $newsletter->gaCampaign = $this->gaCampaign;
    $newsletter->save();
    $result = $tracking->applyGATracking($this->renderedNewsletter, $newsletter, $this->internalHost);
    expect($result)->notEquals($this->renderedNewsletter);
  }

  public function testItGetsGACampaignFromParentNewsletterForPostNotifications() {
    $tracking = new GATracking();
    $notification = Newsletter::create();
    $notification->hydrate([
      'type' => Newsletter::TYPE_NOTIFICATION,
      'ga_campaign' => $this->gaCampaign,
    ]);
    $notification->save();
    $notificationHistory = Newsletter::create();
    $notificationHistory->hydrate([
      'parent_id' => $notification->id,
      'type' => Newsletter::TYPE_NOTIFICATION_HISTORY,
    ]);
    $notificationHistory->save();
    $result = $tracking->applyGATracking($this->renderedNewsletter, $notificationHistory, $this->internalHost);
    expect($result)->notEquals($this->renderedNewsletter);
  }

  public function testItCanAddGAParamsToLinks() {
    $tracking = new GATracking();
    $newsletter = Newsletter::createOrUpdate([
      'ga_campaign' => $this->gaCampaign,
    ]);
    $result = $tracking->applyGATracking($this->renderedNewsletter, $newsletter, $this->internalHost);
    expect($result['text'])->stringContainsString('utm_campaign=' . urlencode($this->gaCampaign));
    expect($result['html'])->stringContainsString('utm_campaign=' . urlencode($this->gaCampaign));
  }

  public function testItKeepsShorcodes() {
    $tracking = new GATracking();
    $newsletter = Newsletter::createOrUpdate([
      'ga_campaign' => $this->gaCampaign,
    ]);
    $result = $tracking->applyGATracking($this->renderedNewsletter, $newsletter, $this->internalHost);
    expect($result['text'])->stringContainsString('email=[subscriber:email]');
    expect($result['html'])->stringContainsString('email=[subscriber:email]');
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
  }
}
