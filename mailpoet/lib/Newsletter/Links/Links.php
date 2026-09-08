<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Links;

use MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\PostProcess\OpenTracking;
use MailPoet\Newsletter\Sending\Placeholders\PlaceholderCollector;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Shortcodes\Categories\Link;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Router\Endpoints\Track as TrackEndpoint;
use MailPoet\Router\Router;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\Helpers;
use MailPoet\Util\pQuery\pQuery as DomParser;
use MailPoet\Util\Security;

class Links {
  const DATA_TAG_CLICK = '[mailpoet_click_data]';
  const DATA_TAG_OPEN = '[mailpoet_open_data]';
  const LINK_TYPE_SHORTCODE = 'shortcode';
  const LINK_TYPE_URL = 'link';

  /** @var LinkTokens */
  private $linkTokens;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var NewsletterLinkRepository */
  private $newsletterLinkRepository;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueueRepository;

  public function __construct(
    LinkTokens $linkTokens,
    SubscribersRepository $subscribersRepository,
    NewsletterLinkRepository $newsletterLinkRepository,
    NewslettersRepository $newslettersRepository,
    SendingQueuesRepository $sendingQueuesRepository
  ) {
    $this->linkTokens = $linkTokens;
    $this->subscribersRepository = $subscribersRepository;
    $this->newsletterLinkRepository = $newsletterLinkRepository;
    $this->newslettersRepository = $newslettersRepository;
    $this->sendingQueueRepository = $sendingQueuesRepository;
  }

  public function process($content, $newsletterId, $queueId) {
    $extractedLinks = $this->extract($content);
    $savedLinks = $this->load($newsletterId, $queueId);
    $processedLinks = $this->hash($extractedLinks, $savedLinks);
    return $this->replace($content, $processedLinks);
  }

  public function extract($content) {
    $extractedLinks = [];
    // extract link shortcodes
    /** @var Shortcodes $shortcodes */
    $shortcodes = ContainerWrapper::getInstance()->get(Shortcodes::class);
    $shortcodes = $shortcodes->extract(
      $content,
      $categories = [Link::CATEGORY_NAME]
    );
    if ($shortcodes) {
      $extractedLinks = array_map(function($shortcode) {
        return [
          'type' => Links::LINK_TYPE_SHORTCODE,
          'link' => $shortcode,
        ];
      }, $shortcodes);
    }
    // extract HTML anchor tags
    $DOM = DomParser::parseStr($content);
    foreach ($DOM->query('a') as $link) {
      if (!$link->href) continue;
      $extractedLinks[] = [
        'type' => self::LINK_TYPE_URL,
        'link' => $link->href,
      ];
    }
    return array_unique($extractedLinks, SORT_REGULAR);
  }

  public function replace($content, $processedLinks) {
    // replace HTML anchor tags
    $DOM = DomParser::parseStr($content);
    foreach ($DOM->query('a') as $link) {
      $linkToReplace = $link->href;
      $replacementLink = (!empty($processedLinks[$linkToReplace]['processed_link'])) ?
        $processedLinks[$linkToReplace]['processed_link'] :
        null;
      if (!$replacementLink) continue;
      $link->setAttribute('href', $replacementLink);
    }
    $content = $DOM->__toString();
    // replace link shortcodes and markdown links
    foreach ($processedLinks as $processedLink) {
      $linkToReplace = $processedLink['link'];
      $replacementLink = $processedLink['processed_link'];
      if ($processedLink['type'] == self::LINK_TYPE_SHORTCODE) {
        $content = str_replace($linkToReplace, $replacementLink, (string)$content);
      }
      $content = preg_replace(
        '/\[(.*?)\](\(' . preg_quote($linkToReplace, '/') . '\))/',
        '[$1](' . $replacementLink . ')',
        (string)$content
      );
    }
    return [
      $content,
      array_values($processedLinks),
    ];
  }

  public function replaceSubscriberData(
    $subscriberId,
    $queueId,
    $content,
    $preview = false
  ) {
    return strtr($content, $this->getTrackingUrlsByDataTag($subscriberId, $queueId, $content, $preview));
  }

  /**
   * @param string $part One of the PlaceholderCollector::PART_* constants
   */
  public function replaceSubscriberDataWithPlaceholders($subscriberId, $queueId, string $content, PlaceholderCollector $collector, string $part): string {
    $placeholders = [];
    foreach ($this->getTrackingUrlsByDataTag($subscriberId, $queueId, $content) as $dataTag => $trackingUrl) {
      $placeholders[$dataTag] = $this->addLinkPlaceholder($collector, $part, $trackingUrl, $dataTag);
    }
    return strtr($content, $placeholders);
  }

  /**
   * For a recipient whose links must not be tracked: every hashed link becomes a placeholder
   * standing for the destination the resolver returns for the stored link, and the open pixel
   * one standing for an inert image. A data tag with no stored link keeps its literal text as
   * the value, as the rendered path leaves it, so that every recipient of a batch gets the same
   * set of placeholders.
   *
   * @param array<string, string> $urlsByHash The queue's stored link URLs by hash, see getUrlsByHash()
   * @param array<string, string> $parts Content keyed by PlaceholderCollector::PART_* constant
   * @param callable(string): string $urlResolver Receives the stored link: a URL, a link shortcode or a personalization tag token
   * @return array<string, string>
   */
  public function replaceHashedLinksWithUntrackedPlaceholders(array $urlsByHash, array $parts, PlaceholderCollector $collector, callable $urlResolver): array {
    foreach ($parts as $part => $content) {
      $placeholders = [];
      preg_match_all($this->getLinkRegex(), $content, $matches);
      foreach (array_unique($matches[1]) as $dataTag) {
        $hash = explode('-', $dataTag)[1] ?? null;
        if (strpos($dataTag, self::DATA_TAG_OPEN) === 0 && $part === PlaceholderCollector::PART_HTML) {
          // Not addHtmlUrl(): esc_url() would drop the data: scheme.
          $placeholders[$dataTag] = $collector->addHtml(OpenTracking::UNTRACKED_PIXEL_SRC, $dataTag);
          continue;
        }
        $value = $hash !== null && isset($urlsByHash[$hash]) ? $urlResolver($urlsByHash[$hash]) : $dataTag;
        $placeholders[$dataTag] = $this->addLinkPlaceholder($collector, $part, $value, $dataTag);
      }
      $parts[$part] = strtr($content, $placeholders);
    }
    return $parts;
  }

  /**
   * Tracking URL for every data tag in the content, keyed by the data tag.
   *
   * @return array<string, string>
   */
  private function getTrackingUrlsByDataTag($subscriberId, $queueId, string $content, bool $preview = false): array {
    $subscriber = $this->subscribersRepository->findOneById($subscriberId);
    if (!$subscriber) {
      throw new InvalidStateException('Subscriber not found for link replacement');
    }
    $linkToken = $this->linkTokens->getToken($subscriber);
    $trackingUrls = [];
    preg_match_all($this->getLinkRegex(), $content, $matches);
    foreach ($matches[1] as $index => $dataTag) {
      if (isset($trackingUrls[$dataTag])) {
        continue;
      }
      $hash = explode('-', $dataTag)[1] ?? null;
      $routerAction = ($matches[2][$index] === self::DATA_TAG_CLICK) ?
        TrackEndpoint::ACTION_CLICK :
        TrackEndpoint::ACTION_OPEN;
      $trackingUrls[$dataTag] = Router::buildRequest(
        TrackEndpoint::ENDPOINT,
        $routerAction,
        $this->createUrlDataObject($subscriber->getId(), $linkToken, $queueId, $hash, $preview)
      );
    }
    return $trackingUrls;
  }

  /**
   * Link URLs go in as is, like replaceSubscriberData() and convertHashedLinksToShortcodesAndUrls()
   * insert them into rendered content.
   */
  private function addLinkPlaceholder(PlaceholderCollector $collector, string $part, string $url, string $token): string {
    if ($part === PlaceholderCollector::PART_HTML) {
      return $collector->addHtml($url, $token);
    }
    if ($part === PlaceholderCollector::PART_SUBJECT) {
      return $collector->addSubjectText($url, $token);
    }
    return $collector->addText($url, $token);
  }

  /**
   * @return array<string, string> Stored URL by hash
   */
  public function getUrlsByHash($queueId): array {
    $urlsByHash = [];
    foreach ($this->newsletterLinkRepository->findBy(['queue' => (int)$queueId]) as $link) {
      $urlsByHash[$link->getHash()] = $link->getUrl();
    }
    return $urlsByHash;
  }

  public function save(array $links, $newsletterId, $queueId) {
    foreach ($links as $link) {
      if (isset($link['id'])) {
        continue;
      }

      if (empty($link['hash']) || empty($link['link'])) {
        continue;
      }

      $newsletter = $this->newslettersRepository->getReference($newsletterId);
      $sendingQueue = $this->sendingQueueRepository->getReference($queueId);

      if (!$newsletter instanceof NewsletterEntity || !$sendingQueue instanceof SendingQueueEntity) {
        continue;
      }

      $newsletterLink = new NewsletterLinkEntity($newsletter, $sendingQueue, $link['link'], $link['hash']);
      $this->newsletterLinkRepository->persist($newsletterLink);
    }

    $this->newsletterLinkRepository->flush();
  }

  public function ensureInstantUnsubscribeLink(array $processedLinks) {
    if (
      in_array(
        NewsletterLinkEntity::INSTANT_UNSUBSCRIBE_LINK_SHORT_CODE,
        array_column($processedLinks, 'link')
      )
    ) {
      return $processedLinks;
    }
    $processedLinks[] = $this->hashLink(
      NewsletterLinkEntity::INSTANT_UNSUBSCRIBE_LINK_SHORT_CODE,
      Links::LINK_TYPE_SHORTCODE
    );
    return $processedLinks;
  }

  /**
   * @param (callable(string): string)|null $urlMapper Applied to every restored URL before it is put back
   */
  public function convertHashedLinksToShortcodesAndUrls($content, $queueId, $convertAll = false, ?callable $urlMapper = null) {
    $urlsByHash = $this->getUrlsByHash($queueId);
    preg_match_all($this->getLinkRegex(), $content, $links);
    $links = array_unique(Helpers::flattenArray($links));
    foreach ($links as $link) {
      $hash = explode('-', $link)[1] ?? null;
      if ($hash === null || !isset($urlsByHash[$hash])) {
        continue;
      }
      $storedUrl = $urlsByHash[$hash];

      // convert either only link shortcodes or all hashes links if "convert all"
      // option is specified
      if (preg_match('/\[link:/', $storedUrl) || $convertAll) {
        $url = $urlMapper ? $urlMapper($storedUrl) : $storedUrl;
        $content = str_replace($link, $url, $content);
      }
    }
    return $content;
  }

  public function getLinkRegex() {
    return sprintf(
      '/((%s|%s)(?:-\w+)?)/',
      preg_quote(self::DATA_TAG_CLICK),
      preg_quote(self::DATA_TAG_OPEN)
    );
  }

  public function createUrlDataObject(
    $subscriberId, $subscriberLinkToken, $queueId, $linkHash, $preview
  ) {
    return [
      (string)$subscriberId,
      $subscriberLinkToken,
      (string)$queueId,
      $linkHash,
      $preview,
    ];
  }

  public function transformUrlDataObject($data) {
    reset($data);
    if (!is_int(key($data))) return $data;
    $transformedData = [];
    $transformedData['subscriber_id'] = (!empty($data[0])) ? $data[0] : false;
    $transformedData['subscriber_token'] = (!empty($data[1])) ? $data[1] : false;
    $transformedData['queue_id'] = (!empty($data[2])) ? $data[2] : false;
    $transformedData['link_hash'] = (!empty($data[3])) ? $data[3] : false;
    $transformedData['preview'] = (!empty($data[4])) ? $data[4] : false;
    return $transformedData;
  }

  private static function hashLink($link, $type) {
    $hash = Security::generateHash();
    return [
      'type' => $type,
      'hash' => $hash,
      'link' => $link,
      // replace link with a temporary data tag + hash
      // it will be further replaced with the proper track API URL during sending
      'processed_link' => self::DATA_TAG_CLICK . '-' . $hash,
    ];
  }

  private function hash($extractedLinks, $savedLinks) {
    $processedLinks = array_map(function($link) {
      $link['type'] = Links::LINK_TYPE_URL;
      $link['link'] = $link['url'];
      $link['processed_link'] = self::DATA_TAG_CLICK . '-' . $link['hash'];
      return $link;
    }, $savedLinks);
    foreach ($extractedLinks as $extractedLink) {
      $link = $extractedLink['link'];
      if (array_key_exists($link, $processedLinks))
        continue;
      // Use URL as a key to map between extracted and processed links
      // regardless of their sequential position (useful for link skips etc.)
      $processedLinks[$link] = $this->hashLink($link, $extractedLink['type']);
    }
    return $processedLinks;
  }

  private function load($newsletterId, $queueId) {
    $links = $this->newsletterLinkRepository->findBy(
      ['newsletter' => $newsletterId, 'queue' => $queueId]
    );

    $savedLinks = [];
    foreach ($links as $link) {
      $savedLinks[$link->getUrl()] = $link->toArray();
    }
    return $savedLinks;
  }
}
