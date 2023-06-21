<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integration;

use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Validator\Builder;

class EmailApiController {
  /** @var NewslettersRepository */
  private $newsletterRepository;

  public function __construct(
    NewslettersRepository $newsletterRepository
  ) {
    $this->newsletterRepository = $newsletterRepository;
  }

  /**
   * @param array $postEmailData - WP_Post data
   * @return array - MailPoet specific email data that will be attached to the post API response
   */
  public function getEmailData($postEmailData): array {
    $newsletter = $this->newsletterRepository->findOneBy(['wpPostId' => $postEmailData['id']]);
    return [
      'id' => $newsletter ? $newsletter->getId() : null,
    ];
  }

  /**
   * Update MailPoet specific data we store with Emails.
   */
  public function saveEmailData(array $data, \WP_Post $emailPost): void {
    // Here comes code saving of MailPoet specific data that will be passed on 'mailpoet_data' attribute
  }

  public function getEmailDataSchema(): array {
    return Builder::object([
      'id' => Builder::integer()->nullable(),
    ])->toArray();
  }
}
