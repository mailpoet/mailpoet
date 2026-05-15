<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Router\Endpoints\ViewInBrowser as ViewInBrowserEndpoint;
use MailPoet\Router\Router;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\WP\Functions as WPFunctions;

class Url {
  public const PUBLIC_SHARE_PATH = '/mailpoet-email/';

  /** @var LinkTokens */
  private $linkTokens;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    LinkTokens $linkTokens,
    WPFunctions $wp
  ) {
    $this->linkTokens = $linkTokens;
    $this->wp = $wp;
  }

  public function getViewInBrowserUrl(
    ?NewsletterEntity $newsletter,
    ?SubscriberEntity $subscriber = null,
    ?SendingQueueEntity $queue = null,
    bool $preview = true
  ) {
    $data = $this->createUrlDataObject($newsletter, $subscriber, $queue, $preview);
    return Router::buildRequest(
      ViewInBrowserEndpoint::ENDPOINT,
      ViewInBrowserEndpoint::ACTION_VIEW,
      $data
    );
  }

  public function getPublicShareUrl(?NewsletterEntity $newsletter): string {
    if (!$newsletter || !$newsletter->getHash()) {
      return '';
    }
    $identifier = $this->getPublicShareIdentifier($newsletter);
    if ($this->usesPrettyPermalinks()) {
      return $this->wp->homeUrl($this->getPublicSharePathPrefix() . $identifier . '/');
    }
    return $this->wp->addQueryArg(
      ['mailpoet_public_email' => $identifier],
      $this->wp->homeUrl('/')
    );
  }

  public function getPublicSharePathPrefix(): string {
    /**
     * Filters the URL path prefix used for the public share route.
     *
     * Useful when the default `/mailpoet-email/` slug collides with an
     * existing page or you want to namespace it under another segment.
     *
     * @param string $prefix Default `/mailpoet-email/`.
     */
    $filtered = $this->wp->applyFilters('mailpoet_public_share_url_prefix', self::PUBLIC_SHARE_PATH);
    $prefix = is_string($filtered) ? trim($filtered, '/') : '';
    if ($prefix === '' || !preg_match('#^[a-zA-Z0-9_/-]+$#', $prefix)) {
      $prefix = trim(self::PUBLIC_SHARE_PATH, '/');
    }
    return '/' . $prefix . '/';
  }

  public function getPublicShareIdentifier(NewsletterEntity $newsletter): string {
    $hash = $newsletter->getHash();
    return sprintf(
      '%s-%s',
      is_string($hash) ? $hash : '',
      $this->getPublicShareSlug($newsletter)
    );
  }

  public function getPublicShareSlug(NewsletterEntity $newsletter): string {
    $slug = $this->wp->sanitizeTitle($newsletter->getCampaignNameOrSubject());
    return $slug ?: 'email';
  }

  /**
   * @return array{hash: string, slug: string|null}|null
   */
  public function parsePublicShareIdentifier(string $identifier): ?array {
    if (!preg_match('/^(?P<hash>[0-9a-f]{12})(?:-(?P<slug>[^\/]+))?$/', $identifier, $matches)) {
      return null;
    }
    return [
      'hash' => $matches['hash'],
      'slug' => $matches['slug'] ?? null,
    ];
  }

  public function usesPrettyPermalinks(): bool {
    return (string)$this->wp->getOption('permalink_structure', '') !== '';
  }

  public function createUrlDataObject(
    ?NewsletterEntity $newsletter,
    ?SubscriberEntity $subscriber,
    ?SendingQueueEntity $queue,
    bool $preview
  ) {
    $newsletterId = $newsletter && $newsletter->getId() ? $newsletter->getId() : 0;
    $newsletterHash = $newsletter && $newsletter->getHash() ? $newsletter->getHash() : 0;
    $sendingQueueId = $queue && $queue->getId() ? $queue->getId() : 0;

    return [
      $newsletterId,
      $newsletterHash,
      $subscriber && $subscriber->getId() ? $subscriber->getId() : 0,
      $subscriber && $subscriber->getId() ? $this->linkTokens->getToken($subscriber) : 0,
      $sendingQueueId,
      (int)$preview,
    ];
  }

  public function transformUrlDataObject($data) {
    reset($data);
    if (!is_int(key($data))) return $data;
    $transformedData = [];
    $transformedData['newsletter_id'] = (!empty($data[0])) ? $data[0] : false;
    $transformedData['newsletter_hash'] = (!empty($data[1])) ? $data[1] : false;
    $transformedData['subscriber_id'] = (!empty($data[2])) ? $data[2] : false;
    $transformedData['subscriber_token'] = (!empty($data[3])) ? $data[3] : false;
    $transformedData['queue_id'] = (!empty($data[4])) ? $data[4] : false;
    $transformedData['preview'] = (!empty($data[5])) ? $data[5] : false;
    return $transformedData;
  }
}
