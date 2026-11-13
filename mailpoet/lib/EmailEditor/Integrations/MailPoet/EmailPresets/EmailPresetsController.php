<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Renderer;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTagManager;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\PersonalizationTagLinkResolver;
use MailPoet\EmailEditor\Integrations\MailPoet\Templates\TemplatesController;
use MailPoet\InvalidStateException;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Preview\SendPreviewController;
use MailPoet\Newsletter\Preview\SendPreviewException;
use MailPoet\WP\Functions as WPFunctions;

class EmailPresetsController {
  const GROUP_ALL = 'all';
  const GROUP_TRASH = 'trash';

  private WPFunctions $wp;

  private TemplatesController $templatesController;

  private NewslettersRepository $newslettersRepository;

  private MailerFactory $mailerFactory;

  private MetaInfo $mailerMetaInfo;

  private PersonalizationTagLinkResolver $linkResolver;

  private PersonalizationTagManager $personalizationTagManager;

  private SendPreviewController $sendPreviewController;

  public function __construct(
    WPFunctions $wp,
    TemplatesController $templatesController,
    NewslettersRepository $newslettersRepository,
    MailerFactory $mailerFactory,
    MetaInfo $mailerMetaInfo,
    PersonalizationTagLinkResolver $linkResolver,
    PersonalizationTagManager $personalizationTagManager,
    SendPreviewController $sendPreviewController
  ) {
    $this->wp = $wp;
    $this->templatesController = $templatesController;
    $this->newslettersRepository = $newslettersRepository;
    $this->mailerFactory = $mailerFactory;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->linkResolver = $linkResolver;
    $this->personalizationTagManager = $personalizationTagManager;
    $this->sendPreviewController = $sendPreviewController;
  }

  public function getPreset(int $postId, bool $includeTrashed = false): ?\WP_Post {
    $post = $this->wp->getPost($postId);
    if (!$post instanceof \WP_Post || $post->post_type !== EmailPresetPostType::NAME) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return null;
    }
    if (!$includeTrashed && $post->post_status === 'trash') { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return null;
    }
    return $post;
  }

  /**
   * A block email or a preset that can be saved as a new preset.
   */
  public function getSource(int $postId): ?\WP_Post {
    $post = $this->wp->getPost($postId);
    if (!$post instanceof \WP_Post || $post->post_status === 'trash') { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return null;
    }
    if (!in_array($post->post_type, EmailEditor::EDITOR_POST_TYPES, true)) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return null;
    }
    return $post;
  }

  /**
   * Creates a blank preset, or a copy of a block email or another preset.
   */
  public function create(string $title, ?\WP_Post $source = null): int {
    $meta = $source ? $this->getSourceMeta($source) : [];
    $meta['_wp_page_template'] = $source ? $this->getTemplateSlug($source) : $this->templatesController->getDefaultTemplateSlug();

    // WordPress unslashes post data on save, which would break escaped block attributes.
    $postId = $this->wp->wpInsertPost($this->wp->wpSlash([
      'post_type' => EmailPresetPostType::NAME,
      'post_status' => EmailPresetPostType::STATUS,
      'post_author' => $this->wp->getCurrentUserId(),
      'post_title' => $title,
      'post_content' => $source ? $source->post_content : '', // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      'meta_input' => $meta,
    ]), true);
    if ($postId instanceof \WP_Error) {
      throw InvalidStateException::create()->withMessage('Could not create the email preset: ' . $postId->get_error_message());
    }
    return (int)$postId;
  }

  public function duplicate(\WP_Post $preset): int {
    // translators: %s is the name of the preset that was copied.
    return $this->create(sprintf(__('Copy of %s', 'mailpoet'), $preset->post_title), $preset); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function rename(\WP_Post $preset, string $title): void {
    $result = $this->wp->wpUpdatePost($this->wp->wpSlash(['ID' => $preset->ID, 'post_title' => $title]), true);
    if ($result instanceof \WP_Error) {
      throw InvalidStateException::create()->withMessage('Could not rename the email preset: ' . $result->get_error_message());
    }
  }

  public function trash(\WP_Post $preset): bool {
    return (bool)$this->wp->wpTrashPost($preset->ID);
  }

  public function restore(\WP_Post $preset): bool {
    // WordPress restores trashed posts as drafts, but presets are always private.
    $keepStatus = function ($newStatus, $postId) use ($preset) {
      return (int)$postId === $preset->ID ? EmailPresetPostType::STATUS : $newStatus;
    };
    $this->wp->addFilter('wp_untrash_post_status', $keepStatus, 10, 2);
    try {
      return (bool)$this->wp->wpUntrashPost($preset->ID);
    } finally {
      $this->wp->removeFilter('wp_untrash_post_status', $keepStatus);
    }
  }

  public function delete(\WP_Post $preset): bool {
    return (bool)$this->wp->wpDeletePost($preset->ID, true);
  }

  /**
   * @param array{group?: string, search?: string, orderby?: string, order?: string, page?: int, per_page?: int} $args
   * @return array{items: \WP_Post[], count: int, groups: array<string, int>}
   */
  public function list(array $args): array {
    $isTrash = ($args['group'] ?? self::GROUP_ALL) === self::GROUP_TRASH;
    $query = $this->wp->wpQuery([
      'post_type' => EmailPresetPostType::NAME,
      'post_status' => $isTrash ? 'trash' : EmailPresetPostType::STATUS,
      's' => $args['search'] ?? '',
      'orderby' => ($args['orderby'] ?? '') === 'title' ? 'title' : 'modified',
      'order' => strtolower($args['order'] ?? '') === 'asc' ? 'ASC' : 'DESC',
      'paged' => max(1, $args['page'] ?? 1),
      'posts_per_page' => max(1, $args['per_page'] ?? 20),
      'ignore_sticky_posts' => true,
    ]);

    $counts = $this->wp->wpCountPosts(EmailPresetPostType::NAME, 'readable');
    return [
      'items' => array_values(array_filter($query->posts ?: [], function ($post): bool {
        return $post instanceof \WP_Post;
      })),
      'count' => (int)$query->found_posts, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      'groups' => [
        self::GROUP_ALL => (int)($counts->{EmailPresetPostType::STATUS} ?? 0),
        self::GROUP_TRASH => (int)($counts->trash ?? 0),
      ],
    ];
  }

  /**
   * Renders the preset with the current WordPress user as the recipient.
   *
   * @return array{subject: string, html: string, text: string}
   */
  public function render(\WP_Post $preset): array {
    $subject = (string)$this->wp->getPostMeta($preset->ID, EmailPresetPostType::META_SUBJECT, true);
    if ($subject === '') {
      $subject = $preset->post_title; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }
    $preheader = (string)$this->wp->getPostMeta($preset->ID, EmailPresetPostType::META_PREHEADER, true);

    // The renderer drops tags that aren't registered yet from the plain-text version.
    $this->personalizationTagManager->extendPersonalizationTagsByAllSubjects();

    // Without the MailPoet context, the coupon block renders nothing instead of its placeholder.
    $contextFilter = function (array $context): array {
      return array_merge($context, [
        'integration' => 'mailpoet',
        'is_real_send' => false,
        'is_preview' => true,
      ]);
    };
    $this->wp->addFilter('woocommerce_email_editor_rendering_email_context', $contextFilter);
    try {
      $rendered = Email_Editor_Container::container()->get(Renderer::class)->render(
        $preset,
        $subject,
        $preheader,
        (string)$this->wp->getBloginfo('language'),
        '<meta name="robots" content="noindex, nofollow" />'
      );
    } finally {
      $this->wp->removeFilter('woocommerce_email_editor_rendering_email_context', $contextFilter);
    }

    $context = $this->sendPreviewController->addSampleDataToContext([
      'recipient_email' => $this->wp->wpGetCurrentUser()->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      'is_preview' => true,
    ]);
    $personalizer = Email_Editor_Container::container()->get(Personalizer::class);
    $personalizer->set_context($context);
    $text = $personalizer->personalize_content((string)$rendered['text'], Personalizer::RENDERING_CONTEXT_TEXT);

    return [
      'subject' => $personalizer->personalize_content($subject, Personalizer::RENDERING_CONTEXT_TEXT),
      'html' => $personalizer->personalize_content((string)$rendered['html'], Personalizer::RENDERING_CONTEXT_HTML),
      'text' => $this->linkResolver->resolveMarkdownLinks($text, $context),
    ];
  }

  public function sendPreview(\WP_Post $preset, string $emailAddress): void {
    $rendered = $this->render($preset);
    $result = $this->mailerFactory->getDefaultMailer()->send(
      [
        'subject' => $rendered['subject'],
        'body' => ['html' => $rendered['html'], 'text' => $rendered['text']],
      ],
      $emailAddress,
      [
        'unsubscribe_url' => $this->wp->homeUrl(),
        'meta' => $this->mailerMetaInfo->getPreviewMetaInfo(),
      ]
    );
    if ($result['response'] === false) {
      throw new SendPreviewException(sprintf(
        // translators: %s contains the actual error message.
        __('The email could not be sent: %s', 'mailpoet'),
        $result['error']->getMessage()
      ));
    }
  }

  public function getEditUrl(int $postId): string {
    return $this->wp->adminUrl('post.php?post=' . $postId . '&action=edit');
  }

  /**
   * @return array<string, string>
   */
  private function getSourceMeta(\WP_Post $source): array {
    if ($source->post_type === EmailPresetPostType::NAME) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $meta = [];
      foreach (EmailPresetPostType::META_KEYS as $metaKey) {
        $meta[$metaKey] = (string)$this->wp->getPostMeta($source->ID, $metaKey, true);
      }
      return $meta;
    }
    $newsletter = $this->newslettersRepository->findOneBy(['wpPost' => $source->ID]);
    if (!$newsletter) {
      return [];
    }
    return [
      EmailPresetPostType::META_SUBJECT => $newsletter->getSubject(),
      EmailPresetPostType::META_PREHEADER => $newsletter->getPreheader(),
    ];
  }

  /**
   * WordPress stores 'default' for a template slug it does not know.
   */
  private function getTemplateSlug(\WP_Post $post): string {
    $slug = $this->wp->getPostMeta($post->ID, '_wp_page_template', true);
    if (!is_string($slug) || $slug === '' || $slug === 'default') {
      return $this->templatesController->getDefaultTemplateSlug();
    }
    return $slug;
  }
}
