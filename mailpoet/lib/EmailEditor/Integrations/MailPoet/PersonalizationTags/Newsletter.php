<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;

class Newsletter {

  private NewslettersRepository $newslettersRepository;

  public function __construct(
    NewslettersRepository $newslettersRepository
  ) {
    $this->newslettersRepository = $newslettersRepository;
  }

  public function getSubject(array $context, array $args = []): string {
    $newsletter = $this->getNewsletter($context);

    return $newsletter ? (string)$newsletter->getSubject() : '';
  }

  private function getNewsletter(array $context): ?NewsletterEntity {
    $newsletterId = $context['newsletter_id'] ?? null;

    return $newsletterId ? $this->newslettersRepository->findOneById($newsletterId) : null;
  }
}
