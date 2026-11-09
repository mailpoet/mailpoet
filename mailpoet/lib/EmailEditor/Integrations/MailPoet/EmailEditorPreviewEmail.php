<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Config\AccessControl;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\EmailPresetPostType;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\EmailPresetsController;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Preview\SendPreviewController;
use MailPoet\WP\Functions as WPFunctions;

class EmailEditorPreviewEmail {
  private NewslettersRepository $newslettersRepository;

  private SendPreviewController $sendPreviewController;

  private WPFunctions $wp;

  private EmailPresetPostType $emailPresetPostType;

  private EmailPresetsController $emailPresetsController;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    SendPreviewController $sendPreviewController,
    WPFunctions $wp,
    EmailPresetPostType $emailPresetPostType,
    EmailPresetsController $emailPresetsController
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->sendPreviewController = $sendPreviewController;
    $this->wp = $wp;
    $this->emailPresetPostType = $emailPresetPostType;
    $this->emailPresetsController = $emailPresetsController;
  }

  /**
   * Sends preview email
   * @param bool|array $postData
   * @return bool|array
   * @throws \Exception
   */
  public function sendPreviewEmail($postData) {
    if (is_bool($postData) || !isset($postData['postId'])) {
      return $postData;
    }
    $post = $this->wp->getPost((int)$postData['postId']);
    $isPreset = $this->emailPresetPostType->isPreset($post);
    if (!$isPreset && (!$post || $post->post_type !== EmailEditor::MAILPOET_EMAIL_POST_TYPE)) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return $postData;
    }

    if (!$this->wp->currentUserCan(AccessControl::PERMISSION_MANAGE_EMAILS)) {
      throw new \Exception(esc_html__('You do not have permission to perform this action.', 'mailpoet'));
    }

    $this->validateData($postData);

    if ($isPreset) {
      $preset = $this->emailPresetsController->getPreset((int)$postData['postId']);
      if (!$preset) {
        throw new \Exception(esc_html__('This email preset does not exist.', 'mailpoet'));
      }
      $this->emailPresetsController->sendPreview($preset, $postData['email']);
      return true;
    }

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
