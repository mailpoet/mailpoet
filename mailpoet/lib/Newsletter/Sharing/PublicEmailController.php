<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sharing;

use InvalidArgumentException;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Newsletter\ViewInBrowser\ViewInBrowserRenderer;

class PublicEmailController {
  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var NewsletterUrl */
  private $newsletterUrl;

  /** @var ShareVisibility */
  private $shareVisibility;

  /** @var ViewInBrowserRenderer */
  private $viewInBrowserRenderer;

  /** @var ShareMetadataBuilder */
  private $shareMetadataBuilder;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    SendingQueuesRepository $sendingQueuesRepository,
    NewsletterUrl $newsletterUrl,
    ShareVisibility $shareVisibility,
    ViewInBrowserRenderer $viewInBrowserRenderer,
    ShareMetadataBuilder $shareMetadataBuilder
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->newsletterUrl = $newsletterUrl;
    $this->shareVisibility = $shareVisibility;
    $this->viewInBrowserRenderer = $viewInBrowserRenderer;
    $this->shareMetadataBuilder = $shareMetadataBuilder;
  }

  public function getNewsletter(string $identifier): NewsletterEntity {
    $data = $this->newsletterUrl->parsePublicShareIdentifier($identifier);
    if (!$data) {
      throw new InvalidArgumentException('Invalid public email identifier.');
    }

    $newsletter = $this->newslettersRepository->findOneBy(['hash' => $data['hash']]);
    if (!$newsletter instanceof NewsletterEntity || !$this->shareVisibility->canShare($newsletter)) {
      throw new InvalidArgumentException('Email is not publicly shareable.');
    }

    return $newsletter;
  }

  public function isCanonicalIdentifier(string $identifier, NewsletterEntity $newsletter): bool {
    return $identifier === $this->newsletterUrl->getPublicShareIdentifier($newsletter);
  }

  public function getCanonicalUrl(NewsletterEntity $newsletter): string {
    return $this->newsletterUrl->getPublicShareUrl($newsletter);
  }

  public function render(NewsletterEntity $newsletter): string {
    $queue = $this->sendingQueuesRepository->findLatestCompletedByNewsletter($newsletter);
    $canonicalUrl = $this->getCanonicalUrl($newsletter);
    $html = $this->viewInBrowserRenderer->renderPublicShare($newsletter, $queue);
    $html = $this->shareMetadataBuilder->injectShareToolbar($html, $newsletter, $canonicalUrl);
    return $this->shareMetadataBuilder->injectMetadata($html, $newsletter, $canonicalUrl);
  }
}
