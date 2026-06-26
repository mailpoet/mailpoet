<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;

class Date {

  private NewslettersRepository $newslettersRepository;
  private WPFunctions $wp;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    WPFunctions $wp
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->wp = $wp;
  }

  public function getDay(array $context, array $args = []): string {
    return $this->format('d', $context);
  }

  public function getDayOrdinal(array $context, array $args = []): string {
    return $this->format('jS', $context);
  }

  public function getDayName(array $context, array $args = []): string {
    return $this->format('l', $context);
  }

  public function getMonth(array $context, array $args = []): string {
    return $this->format('m', $context);
  }

  public function getMonthName(array $context, array $args = []): string {
    return $this->format('F', $context);
  }

  public function getYear(array $context, array $args = []): string {
    return $this->format('Y', $context);
  }

  private function format(string $phpDateFormat, array $context): string {
    return (string)$this->wp->dateI18n($phpDateFormat, $this->getTimestamp($context));
  }

  private function getTimestamp(array $context): int {
    $newsletter = $this->getNewsletter($context);
    if (
      $newsletter
      && $newsletter->getSentAt() instanceof \DateTimeInterface
      && $newsletter->getStatus() === NewsletterEntity::STATUS_SENT
    ) {
      return $newsletter->getSentAt()->getTimestamp();
    }

    return (int)$this->wp->currentTime('timestamp');
  }

  private function getNewsletter(array $context): ?NewsletterEntity {
    $newsletterId = $context['newsletter_id'] ?? null;

    return $newsletterId ? $this->newslettersRepository->findOneById($newsletterId) : null;
  }
}
