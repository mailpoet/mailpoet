<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\ViewInBrowser;

use MailPoet\EmailEditor\Integrations\MailPoet\DependencyNotice;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Sharing\ShareMetadataBuilder;
use MailPoet\Newsletter\Sharing\ShareVisibility;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\SubscribersRepository;

class ViewInBrowserController {
  /** @var LinkTokens */
  private $linkTokens;

  /** @var NewsletterUrl */
  private $newsletterUrl;

  /** @var ViewInBrowserRenderer */
  private $viewInBrowserRenderer;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var DependencyNotice */
  private $dependencyNotice;

  /** @var ShareVisibility */
  private $shareVisibility;

  /** @var ShareMetadataBuilder */
  private $shareMetadataBuilder;

  public function __construct(
    LinkTokens $linkTokens,
    NewsletterUrl $newsletterUrl,
    NewslettersRepository $newslettersRepository,
    ViewInBrowserRenderer $viewInBrowserRenderer,
    SendingQueuesRepository $sendingQueuesRepository,
    DependencyNotice $dependencyNotice,
    SubscribersRepository $subscribersRepository,
    ShareVisibility $shareVisibility,
    ShareMetadataBuilder $shareMetadataBuilder
  ) {
    $this->linkTokens = $linkTokens;
    $this->viewInBrowserRenderer = $viewInBrowserRenderer;
    $this->subscribersRepository = $subscribersRepository;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->newsletterUrl = $newsletterUrl;
    $this->dependencyNotice = $dependencyNotice;
    $this->newslettersRepository = $newslettersRepository;
    $this->shareVisibility = $shareVisibility;
    $this->shareMetadataBuilder = $shareMetadataBuilder;
  }

  public function view(array $data) {
    $data = $this->newsletterUrl->transformUrlDataObject($data);
    $isPreview = !empty($data['preview']);
    $newsletter = $this->getNewsletter($data);
    $subscriber = $this->getSubscriber($data);
    if ($newsletter->getWpPostId() && $this->dependencyNotice->checkDependenciesAndEventuallyShowNotice()) {
      return '';
    }

    // if queue and subscriber exist, subscriber must have received the newsletter
    $queue = isset($data['queue_id']) ? $this->sendingQueuesRepository->findOneById($data['queue_id']) : null;
    if (!$isPreview && $queue && $subscriber->getId() && !$this->sendingQueuesRepository->isSubscriberProcessed($queue, $subscriber)) {
      throw new \InvalidArgumentException("Subscriber did not receive the newsletter yet");
    }

    $html = $this->viewInBrowserRenderer->render($isPreview, $newsletter, $subscriber, $queue);
    if (!empty($data['embed_hide_background'])) {
      $html = $this->hideEmbedBackground($html);
    }
    if (!$isPreview && $this->shareVisibility->canShare($newsletter)) {
      $publicUrl = $this->newsletterUrl->getPublicShareUrl($newsletter);
      // Pass $publicUrl as both the share target and the replaceState target so the
      // tokenised view-in-browser URL is scrubbed from the address bar on load.
      return $this->shareMetadataBuilder->injectShareToolbar($html, $newsletter, $publicUrl, $publicUrl);
    }
    return $html;
  }

  private function hideEmbedBackground(string $html): string {
    $style = '<style id="mailpoet-newsletter-embed-background">html,body{background:transparent!important;}body{padding:0!important;}</style>';
    $headPosition = stripos($html, '</head>');
    if ($headPosition !== false) {
      return substr_replace($html, $style, $headPosition, 0);
    }

    $matches = [];
    if (preg_match('/<html\b[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
      $position = $matches[0][1] + strlen($matches[0][0]);
      return substr_replace($html, $style, $position, 0);
    }

    return $style . $html;
  }

  private function getNewsletter(array $data) {
    // newsletter - ID is mandatory, hash must be set and valid
    if (empty($data['newsletter_id'])) {
      throw new \InvalidArgumentException("Missing 'newsletter_id'");
    }
    if (empty($data['newsletter_hash'])) {
      throw new \InvalidArgumentException("Missing 'newsletter_hash'");
    }

    $newsletter = $this->newslettersRepository->findOneById($data['newsletter_id']);
    if (!$newsletter) {
      throw new \InvalidArgumentException("Invalid 'newsletter_id'");
    }

    if ($data['newsletter_hash'] !== $newsletter->getHash()) {
      throw new \InvalidArgumentException("Invalid 'newsletter_hash'");
    }
    return $newsletter;
  }

  private function getSubscriber(array $data): SubscriberEntity {
    // subscriber is optional; if exists, token must validate
    $subscriber = null;
    if (!empty($data['subscriber_id'])) {
      $subscriber = $this->subscribersRepository->findOneById($data['subscriber_id']);
    }
    if ($subscriber && empty($data['subscriber_token'])) {
      throw new \InvalidArgumentException("Missing 'subscriber_token'");
    }

    if ($subscriber && !$this->linkTokens->verifyToken($subscriber, $data['subscriber_token'])) {
      throw new \InvalidArgumentException("Invalid 'subscriber_token'");
    }

    // if this is a preview and subscriber does not exist,
    // attempt to set subscriber to the current logged-in WP user
    if (!$subscriber && !empty($data['preview'])) {
      $subscriber = $this->subscribersRepository->getCurrentWPUser();
    }

    return $subscriber ?? new SubscriberEntity();
  }
}
