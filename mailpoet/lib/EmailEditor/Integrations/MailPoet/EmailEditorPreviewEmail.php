<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Preview\SendPreviewController;

class EmailEditorPreviewEmail {
  private NewslettersRepository $newslettersRepository;

  private SendPreviewController $sendPreviewController;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    SendPreviewController $sendPreviewController
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->sendPreviewController = $sendPreviewController;
  }

  /**
   * Sends preview email
   * @throws \Exception
   */
  public function sendPreviewEmail($postData): bool {
    $this->validateData($postData);

    $newsletter = $this->fetchNewsletter($postData);
    $subscriber = $postData['email'];

    $this->sendPreviewController->sendPreview($newsletter, $subscriber);

    return true;
  }

  private function validateData($data) {
    if (empty($data['email']) || empty($data['postId'])) {
      throw new \InvalidArgumentException(esc_html__('Missing required data', 'mailpoet'));
    }

    if (!is_email($data['email'])) {
      throw new \InvalidArgumentException(esc_html__('Invalid email address', 'mailpoet'));
    }
  }

  /**
   * @param array $postData
   * @return NewsletterEntity
   * @throws \Exception
   */
  private function fetchNewsletter($postData): NewsletterEntity {
    $newsletter = $this->newslettersRepository->findOneBy(['wpPost' => (int)$postData['postId']]);

    if (!$newsletter instanceof NewsletterEntity) {
      throw new \Exception(esc_html__('This email does not exist.', 'mailpoet'));
    }

    return $newsletter;
  }
}
