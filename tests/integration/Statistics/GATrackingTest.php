<?php

namespace MailPoet\Statistics;

use MailPoet\Models\Newsletter;

class GATrackingTest extends \MailPoetTest {

  /** @var string */
  private $internal_host;

  /** @var string */
  private $ga_campaign;

  /** @var string */
  private $link;

  /** @var string[] */
  private $rendered_newsletter;

  function _before() {
    $this->internal_host = 'newsletters.mailpoet.com';
    $this->ga_campaign = 'Spring email';
    $this->link = add_query_arg(['foo' => 'bar', 'baz' => 'xyz'], 'http://www.mailpoet.com/');
    $this->rendered_newsletter = [
      'html' => '<p><a href="' . $this->link . '">Click here</a>. <a href="http://somehost.com/fff/?abc=123">Do not process this</a> [link:some_link_shortcode]</p>',
      'text' => '[Click here](' . $this->link . '). [Do not process this](http://somehost.com/fff/?abc=123) [link:some_link_shortcode]',
    ];
  }

  function testItConditionallyAppliesGATracking() {
    // No process (empty GA campaign)
    $newsletter = Newsletter::createOrUpdate(['id' => 123]);
    $tracking = new GATracking();
    $result = $tracking->applyGATracking($this->rendered_newsletter, $newsletter, $this->internal_host);
    expect($result)->equals($this->rendered_newsletter);
    // Process (filled GA campaign)
    $newsletter->ga_campaign = $this->ga_campaign;
    $newsletter->save();
    $result = $tracking->applyGATracking($this->rendered_newsletter, $newsletter, $this->internal_host);
    expect($result)->notEquals($this->rendered_newsletter);
  }

  function testItGetsGACampaignFromParentNewsletterForPostNotifications() {
    $tracking = new GATracking();
    $notification = Newsletter::create();
    $notification->hydrate([
      'type' => Newsletter::TYPE_NOTIFICATION,
      'ga_campaign' => $this->ga_campaign,
    ]);
    $notification->save();
    $notification_history = Newsletter::create();
    $notification_history->hydrate([
      'parent_id' => $notification->id,
      'type' => Newsletter::TYPE_NOTIFICATION_HISTORY,
    ]);
    $notification_history->save();
    $result = $tracking->applyGATracking($this->rendered_newsletter, $notification_history, $this->internal_host);
    expect($result)->notEquals($this->rendered_newsletter);
  }

  function testItCanAddGAParamsToLinks() {
    $tracking = new GATracking();
    $newsletter = Newsletter::createOrUpdate([
      'ga_campaign' => $this->ga_campaign,
    ]);
    $result = $tracking->applyGATracking($this->rendered_newsletter, $newsletter, $this->internal_host);
    expect($result['text'])->contains('utm_campaign=' . urlencode($this->ga_campaign));
    expect($result['html'])->contains('utm_campaign=' . urlencode($this->ga_campaign));
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
  }
}
