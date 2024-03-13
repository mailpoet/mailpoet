<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Features\FeaturesController;
use MailPoet\Util\CdnAssetUrl;
use MailPoet\WP\Functions as WPFunctions;
use WP_Post;
use WP_Theme_JSON;

class EmailEditor {
  const MAILPOET_EMAIL_POST_TYPE = 'mailpoet_email';
  const MAILPOET_EMAIL_META_THEME_TYPE = 'mailpoet_email_theme';

  /** @var WPFunctions */
  private $wp;

  /** @var FeaturesController */
  private $featuresController;

  /** @var EmailApiController */
  private $emailApiController;

  /** @var CdnAssetUrl */
  private $cdnAssetUrl;

  public function __construct(
    WPFunctions $wp,
    FeaturesController $featuresController,
    EmailApiController $emailApiController,
    CdnAssetUrl $cdnAssetUrl
  ) {
    $this->wp = $wp;
    $this->featuresController = $featuresController;
    $this->emailApiController = $emailApiController;
    $this->cdnAssetUrl = $cdnAssetUrl;
  }

  public function initialize(): void {
    if (!$this->featuresController->isSupported(FeaturesController::GUTENBERG_EMAIL_EDITOR)) {
      return;
    }
    $this->wp->addFilter('mailpoet_email_editor_post_types', [$this, 'addEmailPostType']);
    $this->wp->addFilter('mailpoet_email_editor_rendering_theme_styles', [$this, 'extendEmailThemeStyles'], 10, 2);
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
      'meta' => [
        [
          'key' => self::MAILPOET_EMAIL_META_THEME_TYPE,
          'args' => [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'object',
            'default' => ['version' => 2],
          ],
        ],
      ],
    ];
    return $postTypes;
  }

  public function extendEmailPostApi() {
    $this->wp->registerRestField(self::MAILPOET_EMAIL_POST_TYPE, 'mailpoet_data', [
      'get_callback' => [$this->emailApiController, 'getEmailData'],
      'update_callback' => [$this->emailApiController, 'saveEmailData'],
      'schema' => $this->emailApiController->getEmailDataSchema(),
    ]);
  }

  public function getEmailDefaultContent(): string {
    return '
      <!-- wp:image {"width":"130px","sizeSlug":"large"} -->
      <figure class="wp-block-image size-large is-resized"><img src="' . esc_url($this->cdnAssetUrl->generateCdnUrl("email-editor/your-logo-placeholder.png")) . '" alt="Your Logo" style="width:130px"/></figure>
      <!-- /wp:image -->
      <!-- wp:heading {"fontSize":"medium","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} -->
      <h2 class="wp-block-heading has-medium-font-size" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)"></h2>
      <!-- /wp:heading -->
      <!-- wp:image -->
      <figure class="wp-block-image"><img alt=""/></figure>
      <!-- /wp:image -->
      <!-- wp:paragraph {"style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
      <p style="padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)"></p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"fontSize":"small"} -->
      <p class="has-small-font-size">' . esc_html__('You received this email because you are subscribed to the [site:title]', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"fontSize":"small"} -->
      <p class="has-small-font-size"><a href="[link:subscription_unsubscribe_url]">' . esc_html__('Unsubscribe', 'mailpoet') . '</a> | <a href="[link:subscription_manage_url]">' . esc_html__('Manage subscription', 'mailpoet') . '</a></p>
      <!-- /wp:paragraph -->
    ';
  }

  public function extendEmailThemeStyles(WP_Theme_JSON $theme, WP_Post $post): WP_Theme_JSON {
    $emailTheme = get_post_meta($post->ID, self::MAILPOET_EMAIL_META_THEME_TYPE, true);
    if ($emailTheme && is_array($emailTheme)) {
      $theme->merge(new WP_Theme_JSON($emailTheme));
    }
    return $theme;
  }
}
