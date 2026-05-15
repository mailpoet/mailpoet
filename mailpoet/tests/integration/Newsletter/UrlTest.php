<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;

class UrlTest extends \MailPoetTest {
  public function testPreviewUrlIsTheSameForNullOrEmptySubscriber() {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('some subject');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setHash(Security::generateHash());
    $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $newsletterUrl = $this->diContainer->get(NewsletterUrl::class);

    $urlNullSubscriber = $newsletterUrl->getViewInBrowserUrl($newsletter);

    $emptySubscriber = new SubscriberEntity();
    $urlEmptySubscriber = $newsletterUrl->getViewInBrowserUrl($newsletter, $emptySubscriber);

    verify($urlNullSubscriber)->equals($urlEmptySubscriber);
  }

  public function testItBuildsPublicShareUrlWithNewsletterHashAndSlug() {
    $this->diContainer->get(WPFunctions::class)->updateOption('permalink_structure', '/%postname%/');
    $newsletter = (new Newsletter())
      ->withSubject('Spring Sale & Updates')
      ->withSentStatus()
      ->create();
    $newsletterUrl = $this->diContainer->get(NewsletterUrl::class);

    $url = $newsletterUrl->getPublicShareUrl($newsletter);
    $identifier = sprintf('%s-spring-sale-updates', $newsletter->getHash());

    verify($url)->equals(home_url(sprintf('/mailpoet-email/%s/', $identifier)));
  }

  public function testItBuildsPublicShareUrlFallbackForPlainPermalinks() {
    $this->diContainer->get(WPFunctions::class)->updateOption('permalink_structure', '');
    $newsletter = (new Newsletter())
      ->withSubject('Plain Permalinks')
      ->withSentStatus()
      ->create();
    $newsletterUrl = $this->diContainer->get(NewsletterUrl::class);

    $url = $newsletterUrl->getPublicShareUrl($newsletter);
    $identifier = sprintf('%s-plain-permalinks', $newsletter->getHash());
    parse_str((string)parse_url($url, PHP_URL_QUERY), $queryParams);

    verify($queryParams)->equals(['mailpoet_public_email' => $identifier]);
  }

  public function testItRespectsThePublicSharePrefixFilter() {
    $this->diContainer->get(WPFunctions::class)->updateOption('permalink_structure', '/%postname%/');
    $newsletter = (new Newsletter())
      ->withSubject('Filtered')
      ->withSentStatus()
      ->create();
    $newsletterUrl = $this->diContainer->get(NewsletterUrl::class);

    $filter = function () {
      return '/share/email/';
    };
    $wp = $this->diContainer->get(WPFunctions::class);
    $wp->addFilter('mailpoet_public_share_url_prefix', $filter);

    try {
      verify($newsletterUrl->getPublicSharePathPrefix())->equals('/share/email/');
      verify($newsletterUrl->getPublicShareUrl($newsletter))
        ->stringContainsString(sprintf('/share/email/%s-filtered/', $newsletter->getHash()));
    } finally {
      $wp->removeFilter('mailpoet_public_share_url_prefix', $filter);
    }
  }

  public function testItIgnoresInvalidPublicSharePrefixFilter() {
    $newsletterUrl = $this->diContainer->get(NewsletterUrl::class);

    $filter = function () {
      return '/bad prefix?/';
    };
    $wp = $this->diContainer->get(WPFunctions::class);
    $wp->addFilter('mailpoet_public_share_url_prefix', $filter);

    try {
      verify($newsletterUrl->getPublicSharePathPrefix())->equals('/mailpoet-email/');
    } finally {
      $wp->removeFilter('mailpoet_public_share_url_prefix', $filter);
    }
  }

  public function testItReturnsEmptyPublicShareUrlWhenNewsletterHasNoHash() {
    $newsletter = (new Newsletter())->create();
    $newsletter->setHash(null);
    $newsletterUrl = $this->diContainer->get(NewsletterUrl::class);

    verify($newsletterUrl->getPublicShareUrl($newsletter))->equals('');
    verify($newsletterUrl->getPublicShareUrl(null))->equals('');
  }
}
