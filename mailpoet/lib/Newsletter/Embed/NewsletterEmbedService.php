<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Embed;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Router\Endpoints\ViewInBrowser as ViewInBrowserEndpoint;
use MailPoet\Router\Router;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterEmbedService {
  public const DEFAULT_HEIGHT = 800;
  public const MIN_HEIGHT = 200;
  public const MAX_HEIGHT = 3000;
  public const DEFAULT_WIDTH = 640;
  public const MIN_WIDTH = 320;
  public const MAX_WIDTH = 1200;
  public const DEFAULT_SELECTOR_LIMIT = 20;
  public const MAX_SELECTOR_LIMIT = 100;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var NewsletterUrl */
  private $newsletterUrl;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    SendingQueuesRepository $sendingQueuesRepository,
    NewsletterUrl $newsletterUrl,
    WPFunctions $wp
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->newsletterUrl = $newsletterUrl;
    $this->wp = $wp;
  }

  public function render(array $attributes = []): string {
    $attributes = $this->sanitizeAttributes($attributes);
    $newsletterId = $attributes['newsletterId'];
    if ($newsletterId <= 0) {
      return '';
    }

    $newsletter = $this->getEmbeddableNewsletter($newsletterId);
    if (!$newsletter instanceof NewsletterEntity) {
      return '';
    }

    $queue = $this->getLatestCompletedQueue($newsletter);
    if (!$queue instanceof SendingQueueEntity) {
      return '';
    }

    $url = $this->getEmbedUrl($newsletter, $queue, $attributes);
    $height = $attributes['height'];
    $width = $attributes['width'];
    $subject = $newsletter->getSubject();
    if ($subject !== null && $subject !== '') {
      // translators: %s is the newsletter subject.
      $title = sprintf(__('MailPoet newsletter: %s', 'mailpoet'), $subject);
    } else {
      $title = __('MailPoet newsletter', 'mailpoet');
    }

    $classNames = 'mailpoet-newsletter-embed';
    if ($attributes['align'] !== '') {
      $classNames .= ' align' . $attributes['align'];
    }

    $html = '<div'
      . ' class="' . $this->wp->escAttr($classNames) . '"'
      . ' style="' . $this->wp->escAttr('text-align:' . $attributes['iframeAlignment'] . ';') . '"'
      . '>';
    $html .= '<iframe'
      . ' class="mailpoet-newsletter-embed-iframe"'
      . ' src="' . $this->wp->escUrl($url) . '"'
      . ' width="' . $this->wp->escAttr((string)$width) . '"'
      . ' height="' . $this->wp->escAttr((string)$height) . '"'
      . ' title="' . $this->wp->escAttr($title) . '"'
      . ' loading="lazy"'
      . ' style="' . $this->wp->escAttr('width:100%;max-width:' . $width . 'px;height:' . $height . 'px;border:0;background:transparent;') . '"'
      . '></iframe>';

    if ($attributes['showFallbackLink']) {
      $html .= '<p'
        . ' class="mailpoet-newsletter-embed-fallback"'
        . ' style="' . $this->wp->escAttr('text-align:' . $attributes['fallbackLinkAlignment'] . ';') . '"'
        . '>'
        . '<a href="' . $this->wp->escUrl($url) . '">'
        . $this->wp->escHtml(__('View newsletter in browser', 'mailpoet'))
        . '</a>'
        . '</p>';
    }

    $html .= '</div>';
    return $html;
  }

  /**
   * @return array{newsletterId: int, height: int, width: int, showFallbackLink: bool, fallbackLinkAlignment: string, iframeAlignment: string, showEmailBackground: bool, align: string}
   */
  public function sanitizeAttributes(array $attributes): array {
    return [
      'newsletterId' => $this->sanitizePositiveId($attributes['newsletterId'] ?? null),
      'height' => $this->sanitizeHeight($attributes['height'] ?? null),
      'width' => $this->sanitizeWidth($attributes['width'] ?? null),
      'showFallbackLink' => $this->sanitizeBoolean($attributes['showFallbackLink'] ?? true),
      'fallbackLinkAlignment' => $this->sanitizeTextAlignment($attributes['fallbackLinkAlignment'] ?? 'center'),
      'iframeAlignment' => $this->sanitizeTextAlignment($attributes['iframeAlignment'] ?? 'center'),
      'showEmailBackground' => $this->sanitizeBoolean($attributes['showEmailBackground'] ?? true),
      'align' => $this->sanitizeAlign($attributes['align'] ?? ''),
    ];
  }

  public function getEmbeddableNewsletter(int $newsletterId): ?NewsletterEntity {
    if ($newsletterId <= 0) {
      return null;
    }
    return $this->newslettersRepository->findEmbeddableNewsletterById($newsletterId);
  }

  public function getLatestCompletedQueue(NewsletterEntity $newsletter): ?SendingQueueEntity {
    return $this->sendingQueuesRepository->findLatestCompletedByNewsletter($newsletter);
  }

  /**
   * @return array<int, array{id: int, label: string, subject: string, sentAt: ?string, type: string, wpPostId?: int}>
   */
  public function getSelectorItems(string $search = '', ?int $limit = null): array {
    $limit = $this->sanitizeSelectorLimit($limit);
    $rows = $this->newslettersRepository->findEmbeddableNewsletterRows($this->wp->sanitizeTextField($search), $limit);

    return array_map(function(array $row): array {
      $sentAt = null;
      if ($row['sentAt'] instanceof \DateTimeInterface) {
        $sentAt = $row['sentAt']->format('Y-m-d H:i:s');
      } elseif (is_string($row['sentAt'] ?? null) && $row['sentAt'] !== '') {
        $sentAt = $row['sentAt'];
      }
      $subject = (string)($row['subject'] ?? '');
      $label = $subject;
      if ($sentAt !== null) {
        $label .= ' - ' . $sentAt;
      }

      $item = [
        'id' => (int)$row['id'],
        'label' => $label,
        'subject' => $subject,
        'sentAt' => $sentAt,
        'type' => (string)($row['type'] ?? ''),
      ];

      if (!empty($row['wpPostId'])) {
        $item['wpPostId'] = (int)$row['wpPostId'];
      }

      return $item;
    }, $rows);
  }

  /**
   * @param mixed $value
   */
  private function sanitizePositiveId($value): int {
    if (!is_scalar($value) || $value === '' || !is_numeric($value)) {
      return 0;
    }

    $id = (int)$value;
    return $id > 0 ? $id : 0;
  }

  /**
   * @param mixed $value
   */
  private function sanitizeHeight($value): int {
    if (!is_scalar($value) || $value === '' || !is_numeric($value)) {
      return self::DEFAULT_HEIGHT;
    }

    $height = (int)$value;
    if ($height <= 0) {
      return self::DEFAULT_HEIGHT;
    }
    if ($height < self::MIN_HEIGHT) {
      return self::MIN_HEIGHT;
    }
    if ($height > self::MAX_HEIGHT) {
      return self::MAX_HEIGHT;
    }
    return $height;
  }

  /**
   * @param mixed $value
   */
  private function sanitizeWidth($value): int {
    if (!is_scalar($value) || $value === '' || !is_numeric($value)) {
      return self::DEFAULT_WIDTH;
    }

    $width = (int)$value;
    if ($width <= 0) {
      return self::DEFAULT_WIDTH;
    }
    if ($width < self::MIN_WIDTH) {
      return self::MIN_WIDTH;
    }
    if ($width > self::MAX_WIDTH) {
      return self::MAX_WIDTH;
    }
    return $width;
  }

  /**
   * @param mixed $value
   */
  private function sanitizeBoolean($value): bool {
    if (is_bool($value)) {
      return $value;
    }

    if (is_scalar($value)) {
      $normalized = strtolower(trim((string)$value));
      return !in_array($normalized, ['0', 'false', 'no', 'off'], true);
    }

    return true;
  }

  /**
   * @param mixed $value
   */
  private function sanitizeTextAlignment($value): string {
    if (!is_string($value)) {
      return 'center';
    }

    return in_array($value, ['left', 'center', 'right'], true) ? $value : 'center';
  }

  /**
   * @param mixed $value
   */
  private function sanitizeAlign($value): string {
    if (!is_string($value)) {
      return '';
    }

    return in_array($value, ['wide', 'full'], true) ? $value : '';
  }

  private function sanitizeSelectorLimit(?int $limit): int {
    if (!$limit || $limit < 1) {
      return self::DEFAULT_SELECTOR_LIMIT;
    }
    return min($limit, self::MAX_SELECTOR_LIMIT);
  }

  /**
   * @param array{newsletterId: int, height: int, width: int, showFallbackLink: bool, fallbackLinkAlignment: string, iframeAlignment: string, showEmailBackground: bool, align: string} $attributes
   */
  private function getEmbedUrl(NewsletterEntity $newsletter, SendingQueueEntity $queue, array $attributes): string {
    if ($attributes['showEmailBackground']) {
      return $this->newsletterUrl->getViewInBrowserUrl($newsletter, null, $queue, true);
    }

    return Router::buildRequest(
      ViewInBrowserEndpoint::ENDPOINT,
      ViewInBrowserEndpoint::ACTION_VIEW,
      [
        'newsletter_id' => $newsletter->getId(),
        'newsletter_hash' => $newsletter->getHash(),
        'subscriber_id' => false,
        'subscriber_token' => false,
        'queue_id' => $queue->getId(),
        'preview' => true,
        'embed_hide_background' => true,
      ]
    );
  }
}
