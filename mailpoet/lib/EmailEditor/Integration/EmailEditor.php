<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integration;

use MailPoet\Config\Env;
use MailPoet\EmailEditor\Core\EmailEditor as CoreEmailEditor;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Features\FeaturesController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;

class EmailEditor {
  const MAILPOET_EMAIL_POST_TYPE = 'mailpoet_email';

  /** @var \MailPoet\EmailEditor\Core\EmailEditor */
  private $coreEmailEditor;

  /** @var WPFunctions */
  private $wp;

  /** @var FeaturesController */
  private $featuresController;

  /** @var NewslettersRepository */
  private $newsletterRepository;

  /** @var EmailApiController */
  private $emailApiController;

  public function __construct(
    CoreEmailEditor $coreEmailEditor,
    WPFunctions $wp,
    FeaturesController $featuresController,
    NewslettersRepository $newsletterRepository,
    EmailApiController $emailApiController
  ) {
    $this->coreEmailEditor = $coreEmailEditor;
    $this->wp = $wp;
    $this->featuresController = $featuresController;
    $this->newsletterRepository = $newsletterRepository;
    $this->emailApiController = $emailApiController;
  }

  public function initialize(): void {
    if (!$this->featuresController->isSupported(FeaturesController::GUTENBERG_EMAIL_EDITOR)) {
      return;
    }
    $this->wp->addFilter('mailpoet_email_editor_post_types', [$this, 'addEmailPostType']);
    $this->wp->addFilter('mailpoet_email_editor_allowed_editor_assets_actions', [$this, 'addAllowedAssetsActions']);
    $this->wp->addFilter('save_post', [$this, 'onEmailSave'], 10, 2);
    $this->wp->addAction('enqueue_block_editor_assets', [$this, 'enqueueAssets']);
    $this->extendEmailPostApi();
    $this->coreEmailEditor->initialize();
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
    $newsletter = $this->newsletterRepository->findOneBy(['wpPostId' => $postId]);
    if ($newsletter) {
      return;
    }
    $newsletter = new NewsletterEntity();
    $newsletter->setWpPostId($postId);
    $newsletter->setSubject('New Editor Email ' . $postId);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD); // We allow only standard emails in the new editor for now
    $this->newsletterRepository->persist($newsletter);
    $this->newsletterRepository->flush();
  }

  /**
   * Email editor attempts to remove all 3rd party enqueue_block_editor_assets to avoid unwanted plugins to interfere with the email editor experience.
   * This method allows us to add our own assets callback to the allowed list.
   */
  public function addAllowedAssetsActions(array $actions): array {
    $actions[] = __CLASS__ . '::enqueueAssets';
    return $actions;
  }

  public function enqueueAssets(): void {
    $screen = $this->wp->getCurrentScreen();
    if (!$screen || self::MAILPOET_EMAIL_POST_TYPE !== $screen->post_type) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return;
    }
    $assetsParams = require_once Env::$assetsPath . '/dist/js/email_editor/email_editor.asset.php';
    $this->wp->wpEnqueueScript(
      'mailpoet_email_editor',
      Env::$assetsUrl . '/dist/js/email_editor/email_editor.js',
      $assetsParams['dependencies'],
      $assetsParams['version'],
      true
    );

    $this->wp->wpEnqueueStyle(
      'mailpoet_email_editor',
      Env::$assetsUrl . '/dist/js/email_editor/email_editor.css',
      [],
      $assetsParams['version']
    );
  }

  public function extendEmailPostApi() {
    $this->wp->registerRestField(self::MAILPOET_EMAIL_POST_TYPE, 'mailpoet_data', [
      'get_callback' => [$this->emailApiController, 'getEmailData'],
      'update_callback' => [$this->emailApiController, 'saveEmailData'],
      'schema' => $this->emailApiController->getEmailDataSchema(),
    ]);
  }
}
