<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Config\AccessControl;
use MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTagManager;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\PersonalizationTagLinkResolver;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Newsletter\Links\Links as NewsletterLinksService;
use MailPoet\Newsletter\NewslettersRepository;

class NewsletterLinks extends APIEndpoint {

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SEGMENTS,
  ];

  private const AUTOMATION_EMAIL_TYPES = [
    NewsletterEntity::TYPE_AUTOMATION,
    NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL,
  ];

  /** @var NewsletterLinkRepository */
  private $newsletterLinkRepository;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var NewsletterLinksService */
  private $newsletterLinks;

  private PersonalizationTagLinkResolver $linkResolver;
  private PersonalizationTagManager $personalizationTagManager;

  public function __construct(
    NewsletterLinkRepository $newsletterLinkRepository,
    NewslettersRepository $newslettersRepository,
    NewsletterLinksService $newsletterLinks,
    PersonalizationTagLinkResolver $linkResolver,
    PersonalizationTagManager $personalizationTagManager
  ) {
    $this->newsletterLinkRepository = $newsletterLinkRepository;
    $this->newslettersRepository = $newslettersRepository;
    $this->newsletterLinks = $newsletterLinks;
    $this->linkResolver = $linkResolver;
    $this->personalizationTagManager = $personalizationTagManager;
  }

  public function get($data = []) {
    $newsletterId = (int)($data['newsletterId'] ?? 0);
    $newsletter = $this->newslettersRepository->findOneById($newsletterId);
    if (!$newsletter instanceof NewsletterEntity) {
      return $this->successResponse([]);
    }

    if (in_array($newsletter->getType(), self::AUTOMATION_EMAIL_TYPES, true)) {
      return $this->successResponse($this->getAutomationLinks($newsletter));
    }

    $links = $this->newsletterLinkRepository->findBy(['newsletter' => $newsletterId]);
    $response = [];
    foreach ($links as $link) {
      $response[] = [
        'id' => $link->getId(),
        'url' => $this->getLabel($link->getUrl()),
      ];
    }
    return $this->successResponse($response);
  }

  private function getAutomationLinks(NewsletterEntity $newsletter): array {
    $newsletterId = $newsletter->getId();
    if (!$newsletterId) {
      return [];
    }

    // Subject-dependent tags (e.g. order tags) are registered per automation; needed for their labels
    $automationId = $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_AUTOMATION_ID);
    if (is_numeric($automationId)) {
      $this->personalizationTagManager->extendPersonalizationTagsByAutomationSubjects((int)$automationId);
    }

    $urls = [];
    foreach ($this->newsletterLinkRepository->findUrlsByNewsletterId($newsletterId) as $url) {
      $this->addUrl($urls, $url);
    }
    foreach ($this->extractUrlsFromNewsletterBody($newsletter) as $url) {
      $this->addUrl($urls, $url);
    }

    return array_map(function(string $url): array {
      return [
        'id' => $url,
        'url' => $this->getLabel($url),
      ];
    }, array_values($urls));
  }

  /**
   * Links stored as personalization tag tokens are shown under the tag's name.
   */
  private function getLabel(string $url): string {
    return $this->linkResolver->getDisplayName($url) ?? $url;
  }

  /**
   * @return string[]
   */
  private function extractUrlsFromNewsletterBody(NewsletterEntity $newsletter): array {
    $body = $newsletter->getBody();
    if (!is_array($body)) {
      return [];
    }

    $urls = [];
    $this->collectUrlsFromBody($body['content'] ?? $body, $urls);
    return array_values($urls);
  }

  /**
   * @param mixed $bodyPart
   * @param array<string, string> $urls
   */
  private function collectUrlsFromBody($bodyPart, array &$urls): void {
    if (!is_array($bodyPart)) {
      return;
    }

    foreach ($bodyPart as $key => $value) {
      if (is_string($value)) {
        if (in_array($key, ['url', 'link'], true)) {
          $this->addUrl($urls, $value);
        }
        // Links::extract parses HTML and scans for shortcodes; skip when neither marker is present
        // to avoid running a DOM parse on every plain-text leaf of the body tree.
        if (strpos($value, '<') !== false || strpos($value, '[') !== false) {
          foreach ($this->newsletterLinks->extract($value) as $link) {
            $this->addUrl($urls, $link['link'] ?? '');
          }
        }
      }

      if (is_array($value)) {
        $this->collectUrlsFromBody($value, $urls);
      }
    }
  }

  /**
   * @param array<string, string> $urls
   */
  private function addUrl(array &$urls, string $url): void {
    $url = trim($url);
    if (!$this->isSelectableUrl($url)) {
      return;
    }
    $urls[$url] = $url;
  }

  private function isSelectableUrl(string $url): bool {
    if ($url === '') {
      return false;
    }
    // strpos returns false when '[' is absent and 0 when it's the first char; !== 0 covers both
    // "no leading bracket" cases. Allow [link:…] shortcodes and personalization tag tokens through;
    // reject other tokens like [postLink].
    return strpos($url, '[') !== 0 || strpos($url, '[link:') === 0 || $this->linkResolver->isTokenUrl($url);
  }
}
