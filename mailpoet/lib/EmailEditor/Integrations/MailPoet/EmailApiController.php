<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\NotFoundException;
use MailPoet\UnexpectedValueException;
use MailPoet\Validator\Builder;

class EmailApiController {
  /** @var NewslettersRepository */
  private $newsletterRepository;

  /** @var NewsletterUrl */
  private $newsletterUrl;

  public function __construct(
    NewslettersRepository $newsletterRepository,
    NewsletterUrl $newsletterUrl
  ) {
    $this->newsletterRepository = $newsletterRepository;
    $this->newsletterUrl = $newsletterUrl;
  }

  /**
   * @param array $postEmailData - WP_Post data
   * @return array - MailPoet specific email data that will be attached to the post API response
   */
  public function getEmailData($postEmailData): array {
    $newsletter = $this->newsletterRepository->findOneBy(['wpPost' => $postEmailData['id']]);
    return [
      'id' => $newsletter ? $newsletter->getId() : null,
      'subject' => $newsletter ? $newsletter->getSubject() : '',
      'preheader' => $newsletter ? $newsletter->getPreheader() : '',
      'preview_url' => $this->newsletterUrl->getViewInBrowserUrl($newsletter),
      'theme' => get_post_meta($postEmailData['id'], \MailPoet\EmailEditor\Engine\EmailEditor::MAILPOET_EMAIL_META_THEME_TYPE, true),
    ];
  }

  /**
   * Update MailPoet specific data we store with Emails.
   */
  public function saveEmailData(array $data, \WP_Post $emailPost): void {
    $newsletter = $this->newsletterRepository->findOneById($data['id']);
    if (!$newsletter) {
      throw new NotFoundException('Newsletter was not found');
    }
    if ($newsletter->getWpPostId() !== $emailPost->ID) {
      throw new UnexpectedValueException('Newsletter ID does not match the post ID');
    }

    update_post_meta($emailPost->ID, \MailPoet\EmailEditor\Engine\EmailEditor::MAILPOET_EMAIL_META_THEME_TYPE, $data['theme']);

    $newsletter->setSubject($data['subject']);
    $newsletter->setPreheader($data['preheader']);
    $this->newsletterRepository->flush();
  }

  public function getEmailDataSchema(): array {
    return Builder::object([
      'id' => Builder::integer()->nullable(),
      'subject' => Builder::string(),
      'preheader' => Builder::string(),
      'preview_url' => Builder::string(),
      'theme' => Builder::object(),
    ])->toArray();
  }
}
