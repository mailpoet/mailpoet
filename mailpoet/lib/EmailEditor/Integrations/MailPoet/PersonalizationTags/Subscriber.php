<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use MailPoet\Subscribers\SubscribersRepository;

class Subscriber {

  private SubscribersRepository $subscribersRepository;

  public function __construct(
    SubscribersRepository $subscribersRepository
  ) {
    $this->subscribersRepository = $subscribersRepository;
  }

  public function getFirstName(array $context, array $args = []): string {
    $subscriberEmail = $context['recipient_email'] ?? null;
    $subscriber = $subscriberEmail ? $this->subscribersRepository->findOneBy(['email' => $subscriberEmail]) : null;

    return ($subscriber && $subscriber->getFirstName()) ? $subscriber->getFirstName() : $args['default'] ?? '';
  }

  public function getLastName(array $context, array $args = []): string {
    $subscriberEmail = $context['recipient_email'] ?? null;
    $subscriber = $subscriberEmail ? $this->subscribersRepository->findOneBy(['email' => $subscriberEmail]) : null;

    return ($subscriber && $subscriber->getLastName()) ? $subscriber->getLastName() : $args['default'] ?? '';
  }

  public function getEmail(array $context, array $args = []): string {
    return $context['recipient_email'] ?? '';
  }
}
