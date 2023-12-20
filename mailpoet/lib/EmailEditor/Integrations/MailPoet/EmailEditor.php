<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\WpPostEntity;
use MailPoet\Features\FeaturesController;
use MailPoet\Newsletter\NewsletterSaveController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class EmailEditor {
  const MAILPOET_EMAIL_POST_TYPE = 'mailpoet_email';

  /** @var WPFunctions */
  private $wp;

  /** @var FeaturesController */
  private $featuresController;

  /** @var NewslettersRepository */
  private $newsletterRepository;

  /** @var EmailApiController */
  private $emailApiController;

  /** @var EntityManager */
  private $entityManager;

  /** @var NewsletterSaveController */
  private $newsletterSaveController;

  public function __construct(
    WPFunctions $wp,
    FeaturesController $featuresController,
    NewslettersRepository $newsletterRepository,
    EmailApiController $emailApiController,
    EntityManager $entityManager,
    NewsletterSaveController $newsletterSaveController
  ) {
    $this->wp = $wp;
    $this->featuresController = $featuresController;
    $this->newsletterRepository = $newsletterRepository;
    $this->emailApiController = $emailApiController;
    $this->entityManager = $entityManager;
    $this->newsletterSaveController = $newsletterSaveController;
  }

  public function initialize(): void {
    if (!$this->featuresController->isSupported(FeaturesController::GUTENBERG_EMAIL_EDITOR)) {
      return;
    }
    $this->wp->addFilter('mailpoet_email_editor_post_types', [$this, 'addEmailPostType']);
    $this->wp->addFilter('save_post', [$this, 'onEmailSave'], 10, 2);
    $this->extendEmailPostApi();
  }

  public function addEmailPostType(array $postTypes): array {
    $postTypes[] = [
      'name' => self::MAILPOET_EMAIL_POST_TYPE,
      'args' => [
        'labels' => [
          'name' => __('Emails', 'mailpoet'),
          'singular_name' => __('Email', 'mailpoet'),
        ],
        'rewrite' => ['slug' => self::MAILPOET_EMAIL_POST_TYPE],
      ],
    ];
    return $postTypes;
  }

  /**
   * This method ensures that saved email has an associated newsletter entity.
   * In the future we will also need to save additional parameters like subject, type, etc.
   */
  public function onEmailSave($postId, \WP_Post $post): void {
    if ($post->post_type !== self::MAILPOET_EMAIL_POST_TYPE) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return;
    }
    $newsletter = $this->newsletterRepository->findOneBy(['wpPost' => $postId]);
    if ($newsletter) {
      return;
    }
    $newsletter = $this->newsletterSaveController->save([
      'subject' => __('Subject', 'mailpoet'),
      'type' => NewsletterEntity::TYPE_STANDARD, // We allow only standard emails in the new editor for now
    ]);
    $newsletter->setWpPost($this->entityManager->getReference(WpPostEntity::class, $postId));
    $this->newsletterRepository->flush();
  }

  public function extendEmailPostApi() {
    $this->wp->registerRestField(self::MAILPOET_EMAIL_POST_TYPE, 'mailpoet_data', [
      'get_callback' => [$this->emailApiController, 'getEmailData'],
      'update_callback' => [$this->emailApiController, 'saveEmailData'],
      'schema' => $this->emailApiController->getEmailDataSchema(),
    ]);
  }
}
