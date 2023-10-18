<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter\Links;

use MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Router\Router;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterLink as NewsletterLinkFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class LinksTest extends \MailPoetTest {
  /** @var Links */
  private $links;

  /** @var NewsletterLinkFactory */
  private $newsletterLinkFactory;

  /** @var NewsletterLinkRepository */
  private $newsletterLinkRepository;

  /** @var NewsletterEntity */
  private $newsletter;

  public function _before() {
    $this->links = $this->diContainer->get(Links::class);
    $newsletterFactory = new NewsletterFactory();
    $this->newsletter = $newsletterFactory->withSendingQueue()->create();
    $this->newsletterLinkFactory = new NewsletterLinkFactory($this->newsletter);
    $this->newsletterLinkRepository = $this->diContainer->get(NewsletterLinkRepository::class);
  }

  public function testItOnlyExtractsLinksFromAnchorTags() {
    $template = '<a href="http://example.com">some site</a> http://link2.com <img src="http://link3.com">';
    $result = $this->links->extract($template);

    verify($result[0])->equals(
      [
        'type' => Links::LINK_TYPE_URL,
        'link' => 'http://example.com',
      ]
    );
  }

  public function testItOnlyHashesAndReplacesLinksInAnchorTags() {
    $template = '<a href="http://example.com"><img src="http://example.com" /></a>';
    $result = $this->links->process($template, 0, 0);
    verify($result[0])->equals(
      sprintf(
        '<a href="%s-%s"><img src="http://example.com" /></a>',
        Links::DATA_TAG_CLICK,
        $result[1][0]['hash']
      )
    );
  }

  public function testItDoesNotRehashExistingLinks() {
    $link = $this->newsletterLinkFactory
      ->withHash('123')
      ->withUrl('http://example.com')
      ->create();

    $template = '<a href="http://example.com"><img src="http://example.com" /></a>';
    $result = $this->links->process($template, $link->getNewsletter()->getId(), $link->getQueue()->getId());
    verify($result[0])->equals(
      sprintf(
        '<a href="%s-%s"><img src="http://example.com" /></a>',
        Links::DATA_TAG_CLICK,
        123
      )
    );
  }

  public function testItCanExtactLinkShortcodes() {
    $template = '[notlink:shortcode] [link:some_link_shortcode]';
    $result = $this->links->extract($template);

    verify($result[0])->equals(
      [
        'type' => Links::LINK_TYPE_SHORTCODE,
        'link' => '[link:some_link_shortcode]',
      ]
    );
  }

  public function testItHashesAndReplacesLinks() {
    $template = '<a href="http://example.com">some site</a> [link:some_link_shortcode]';
    list($updatedContent, $hashedLinks) = $this->links->process($template, 0, 0);

    // 2 links were hashed
    verify(count($hashedLinks))->equals(2);
    // links in returned content were replaced with hashes
    verify($updatedContent)
      ->stringContainsString(Links::DATA_TAG_CLICK . '-' . $hashedLinks[0]['hash']);
    verify($updatedContent)
      ->stringContainsString(Links::DATA_TAG_CLICK . '-' . $hashedLinks[1]['hash']);
    verify($updatedContent)->stringNotContainsString('link');
  }

  public function testItHashesAndReplacesLinksWithSpecialCharacters() {
    $template = '<a href="http://сайт.cóm/彌撒時間">some site</a>';
    $result = $this->links->process($template, 0, 0);
    verify($result[0])->equals(
      sprintf(
        '<a href="%s-%s">some site</a>',
        Links::DATA_TAG_CLICK,
        $result[1][0]['hash']
      )
    );
  }

  public function testItDoesNotReplaceUnprocessedLinks() {
    $template = '<a href="http://example.com">some site</a> [link:some_link_shortcode]';

    $processedLinks = [
      'http://example.com' => [
        'type' => Links::LINK_TYPE_URL,
        'link' => 'http://example.com',
        'processed_link' => 'replace by this',
      ],
    ];

    [$updatedContent, $replacedLinks] = $this->links->replace($template, $processedLinks);

    verify($replacedLinks)->arrayCount(1);
    // links in returned content were replaced with hashes
    verify($updatedContent)
      ->stringContainsString('replace by this');
    verify($updatedContent)
      ->stringContainsString('[link:some_link_shortcode]');
    verify($updatedContent)->stringNotContainsString('http://example.com');
  }

  public function testItCreatesAndTransformsUrlDataObject() {
    $subscriberEmail = 'test@example.com';
    $data = [
      'subscriber_id' => 1,
      'subscriber_token' => md5($subscriberEmail),
      'queue_id' => 2,
      'link_hash' => 'hash',
      'preview' => false,
    ];
    $urlDataObject = $this->links->createUrlDataObject(
      $data['subscriber_id'],
      $data['subscriber_token'],
      $data['queue_id'],
      $data['link_hash'],
      $data['preview']
    );
    // URL data object should be an indexed array
    verify($urlDataObject)->equals(array_values($data));
    // transformed URL object should be an associative array
    $transformedUrlDataObject = $this->links->transformUrlDataObject($urlDataObject);
    verify($transformedUrlDataObject)->equals($data);
  }

  public function testItReplacesHashedLinksWithSubscriberData() {
    $subscriberFactory = new SubscriberFactory();
    $subscriber = $subscriberFactory->create();
    $queue = $this->newsletter->getLatestQueue();

    $this->assertInstanceOf(SendingQueueEntity::class, $queue);

    $template = '<a href="[mailpoet_click_data]-1234">some site</a> <img src="[mailpoet_open_data]"/>';
    $result = $this->links->replaceSubscriberData($subscriber->getId(), $queue->getId(), $template);

    // there are no click/open data tags
    verify($result)->stringNotContainsString(Links::DATA_TAG_CLICK);
    verify($result)->stringNotContainsString(Links::DATA_TAG_OPEN);

    // data tags were converted to URLs
    expect($result)
      ->regExp('/<a href="http.*?' . Router::NAME . '&endpoint=track&action=click&data=.*?>/');
    expect($result)
      ->regExp('/<img src="http.*?' . Router::NAME . '&endpoint=track&action=open&data=.*?>/');

    // data was properly encoded
    preg_match_all('/data=(?P<data>.*?)"/', $result, $result);
    foreach ($result['data'] as $data) {
      $data = Router::decodeRequestData($data);
      $data = $this->links->transformUrlDataObject($data);
      verify($data['subscriber_id'])->equals($subscriber->getId());
      verify($data['queue_id'])->equals($queue->getId());
      verify(isset($data['subscriber_token']))->true();
    }
  }

  public function testItCanSaveLinks() {
    $links = [
      [
        'link' => 'http://example.com',
        'hash' => '123',
      ],
    ];
    $newsletterId = $this->newsletter->getId();
    $latestQueue = $this->newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $latestQueue);
    $queueId = $latestQueue->getId();

    $this->links->save(
      $links,
      $newsletterId,
      $queueId
    );

    // 1 database record was created
    $newsletterLink = $this->newsletterLinkRepository->findOneBy(['newsletter' => $newsletterId, 'queue' => $queueId]);
    $this->assertInstanceOf(NewsletterLinkEntity::class, $newsletterLink);
    verify($newsletterLink->getHash())->equals('123');
    verify($newsletterLink->getUrl())->equals('http://example.com');
  }

  public function testItCanReuseAlreadySavedLinks() {
    $newsletterLink1 = $this->newsletterLinkFactory
      ->withHash('123')
      ->withUrl('http://example.com')
      ->create();
    $tableName = $this->entityManager->getClassMetadata(NewsletterLinkEntity::class)->getTableName();
    $this->entityManager->getConnection()
      ->executeStatement(
        "UPDATE $tableName SET queue_id = 2 WHERE id = ?", [$newsletterLink1->getId()]
      );

    $this->newsletterLinkFactory
      ->withHash('456')
      ->withUrl('http://demo.com')
      ->create();

    list($content, $links) = $this->links->process('<a href="http://example.com">x</a>', $this->newsletter->getId(), 2);
    verify(is_array($links))->true();
    verify(count($links))->equals(1);
    verify($links[0]['hash'])->equals('123');
    verify($links[0]['url'])->equals('http://example.com');
  }

  public function testItMatchesHashedLinks() {
    $regex = $this->links->getLinkRegex();
    verify((boolean)preg_match($regex, '[some_tag]-123'))->false();
    verify((boolean)preg_match($regex, '[some_tag]'))->false();
    verify((boolean)preg_match($regex, '[mailpoet_click_data]-123'))->true();
    verify((boolean)preg_match($regex, '[mailpoet_open_data]'))->true();
  }

  public function testItCanConvertOnlyHashedLinkShortcodes() {
    // create newsletter link association
    $newsletterLink = $this->newsletterLinkFactory
      ->withHash('90e56')
      ->withUrl('[link:newsletter_view_in_browser_url]')
      ->create();

    $content = '
      <a href="[mailpoet_click_data]-90e56">View in browser</a>
      <a href="[mailpoet_click_data]-123">Some link</a>';

    $result = $this->links->convertHashedLinksToShortcodesAndUrls($content, $newsletterLink->getQueue()->getId());

    verify($result)->stringContainsString($newsletterLink->getUrl());
    verify($result)->stringContainsString('[mailpoet_click_data]-123');
  }

  public function testItCanEnsureThatUnsubscribeLinkIsAmongProcessedLinks() {
    $links = [
      [
        'link' => 'http://example.com',
        'type' => Links::LINK_TYPE_URL,
        'processed_link' => '[mailpoet_click_data]-123',
        'hash' => 'abcdfgh',
      ],
    ];
    $links = $this->links->ensureInstantUnsubscribeLink($links);
    verify(count($links))->equals(2);
    verify($links[1]['link'])->equals(NewsletterLinkEntity::INSTANT_UNSUBSCRIBE_LINK_SHORT_CODE);
    verify($links[1]['type'])->equals(Links::LINK_TYPE_SHORTCODE);
    verify($links[1])->arrayHasKey('processed_link');
    verify($links[1])->arrayHasKey('hash');
    $links = $this->links->ensureInstantUnsubscribeLink($links);
    verify(count($links))->equals(2);
  }

  public function testItCanConvertAllHashedLinksToUrls() {
    // create newsletter link associations
    $newsletterLink1 = $this->newsletterLinkFactory
      ->withHash('90e56')
      ->withUrl('[link:newsletter_view_in_browser_url]')
      ->create();

    $newsletterLink2 = $this->newsletterLinkFactory
      ->withHash('123')
      ->withUrl('http://google.com')
      ->create();

    $content = '
      <a href="[mailpoet_click_data]-90e56">View in browser</a>
      <a href="[mailpoet_click_data]-123">Some link</a>';

    $result = $this->links->convertHashedLinksToShortcodesAndUrls($content, $newsletterLink1->getQueue()->getId(), $convertAll = true);
    verify($result)->stringContainsString($newsletterLink1->getUrl());
    verify($result)->stringContainsString($newsletterLink2->getUrl());
  }
}
