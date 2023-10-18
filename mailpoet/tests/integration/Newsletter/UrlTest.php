<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Util\Security;

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
}
