<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Links;
use MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;

class LinksTest extends \MailPoetTest {
  /** @var Links */
  private $links;

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var SendingQueueEntity */
  private $queue;

  /** @var NewsletterLinkRepository */
  private $newsletterLinkRepository;

  protected function _before() {
    parent::_before();
    $newsletterFactory = new NewsletterFactory();
    $this->newsletter = $newsletterFactory->withSendingQueue()->create();
    $this->queue = $this->newsletter->getQueues()->first();
    $this->links = $this->diContainer->get(Links::class);
    $this->newsletterLinkRepository = $this->diContainer->get(NewsletterLinkRepository::class);
  }

  public function testItCanSaveLinks() {
    $links = [
      [
        'link' => 'http://example.com',
        'hash' => 'some_hash',
      ],
    ];
    $queue = (object)['id' => $this->queue->getId()];

    $this->links->saveLinks($links, $this->newsletter, $queue);

    $newsletterLink = $this->newsletterLinkRepository->findOneBy(['hash' => $links[0]['hash']]);
    $this->assertInstanceOf(NewsletterLinkEntity::class, $newsletterLink);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterLink->getNewsletter());
    expect($newsletterLink->getNewsletter()->getId())->equals($this->newsletter->getId());
    $this->assertInstanceOf(SendingQueueEntity::class, $newsletterLink->getQueue());
    expect($newsletterLink->getQueue()->getId())->equals($this->queue->getId());
    expect($newsletterLink->getUrl())->equals($links[0]['link']);
  }

  public function testItCanHashAndReplaceLinks() {
    $renderedNewsletter = [
      'html' => '<a href="http://example.com">Example Link</a>',
      'text' => '<a href="http://example.com">Example Link</a>',
    ];
    $result = $this->links->hashAndReplaceLinks($renderedNewsletter, 0, 0);
    $processedRenderedNewsletterBody = $result[0];
    $processedAndHashedLinks = $result[1];
    expect($processedRenderedNewsletterBody['html'])
      ->stringContainsString($processedAndHashedLinks[0]['hash']);
    expect($processedRenderedNewsletterBody['text'])
      ->stringContainsString($processedAndHashedLinks[0]['hash']);
    expect($processedAndHashedLinks[0]['link'])->equals('http://example.com');
  }

  public function testItCanProcessRenderedBody() {
    $renderedNewsletter = [
      'html' => '<a href="http://example.com">Example Link</a>',
      'text' => '<a href="http://example.com">Example Link</a>',
    ];

    $result = $this->links->process($renderedNewsletter, $this->newsletter, $this->queue);

    $newsletterLink = $this->newsletterLinkRepository->findOneBy(['newsletter' => $this->newsletter->getId()]);
    $this->assertInstanceOf(NewsletterLinkEntity::class, $newsletterLink);
    expect($result['html'])->stringContainsString($newsletterLink->getHash());
  }

  public function testItCanEnsureThatInstantUnsubscribeLinkIsAlwaysPresent() {
    $renderedNewsletter = [
      'html' => '<a href="http://example.com">Example Link</a>',
      'text' => '<a href="http://example.com">Example Link</a>',
    ];

    $this->links->process($renderedNewsletter, $this->newsletter, $this->queue);

    $unsubscribeCount = $this->newsletterLinkRepository->countBy(
      [
        'newsletter' => $this->newsletter->getId(),
        'url' => NewsletterLinkEntity::INSTANT_UNSUBSCRIBE_LINK_SHORT_CODE,
      ]
    );
    expect($unsubscribeCount)->equals(1);
  }
}
