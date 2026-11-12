<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets;

use MailPoet\Features\FeaturesController;
use MailPoet\WP\Functions as WPFunctions;

/**
 * Saved block email presets. A preset has no newsletter row, so it can never be sent.
 */
class EmailPresetPostType {
  const NAME = 'mailpoet_preset';
  // Private keeps the preview URL away from visitors.
  const STATUS = 'private';

  const META_DESCRIPTION = 'mailpoet_preset_description';
  const META_SUBJECT = 'mailpoet_preset_subject';
  const META_PREHEADER = 'mailpoet_preset_preheader';
  const META_KEYS = [self::META_DESCRIPTION, self::META_SUBJECT, self::META_PREHEADER];

  private FeaturesController $featuresController;

  private WPFunctions $wp;

  public function __construct(
    FeaturesController $featuresController,
    WPFunctions $wp
  ) {
    $this->featuresController = $featuresController;
    $this->wp = $wp;
  }

  public function isEnabled(): bool {
    return $this->featuresController->isSupported(FeaturesController::FEATURE_EMAIL_PRESETS);
  }

  public function isPreset(?\WP_Post $post): bool {
    return $post instanceof \WP_Post && $post->post_type === self::NAME && $this->isEnabled(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  /**
   * @param array<string, string> $capabilities
   * @return array{name: string, args: array<string, mixed>}
   */
  public function getDefinition(array $capabilities): array {
    return [
      'name' => self::NAME,
      'args' => [
        'labels' => [
          'name' => __('Email presets', 'mailpoet'),
          'singular_name' => __('Email preset', 'mailpoet'),
        ],
        // A query-string preview URL works without flushing the rewrite rules when the flag is turned on.
        'rewrite' => false,
        'map_meta_cap' => true,
        'capabilities' => $capabilities,
      ],
    ];
  }

  public function registerMeta(): void {
    $sanitizeCallbacks = [
      self::META_DESCRIPTION => 'sanitize_textarea_field',
      self::META_SUBJECT => [$this, 'sanitizeTextWithTags'],
      self::META_PREHEADER => [$this, 'sanitizeTextWithTags'],
    ];
    foreach ($sanitizeCallbacks as $metaKey => $sanitizeCallback) {
      $this->wp->registerPostMeta(self::NAME, $metaKey, [
        'type' => 'string',
        'single' => true,
        'default' => '',
        'show_in_rest' => true,
        'sanitize_callback' => $sanitizeCallback,
        'auth_callback' => function ($allowed, $metaKey, $postId): bool {
          return (bool)$this->wp->currentUserCan('edit_post', (int)$postId);
        },
      ]);
    }
  }

  /**
   * Like sanitize_text_field(), but keeps personalization tags such as <!--[user/firstname]-->.
   */
  public function sanitizeTextWithTags($value): string {
    $value = (string)$value;
    $tags = [];
    $offset = 0;
    while (($start = strpos($value, '<!--[', $offset)) !== false) {
      $end = strpos($value, ']-->', $start);
      if ($end === false) {
        break;
      }
      $tag = substr($value, $start, $end + 4 - $start);
      if (strpbrk(substr($tag, 4, -3), '<>') !== false) {
        $offset = $start + 1;
        continue;
      }
      $placeholder = '{{mailpoet_preset_tag_' . count($tags) . '}}';
      $tags[$placeholder] = $tag;
      $value = substr_replace($value, $placeholder, $start, strlen($tag));
      $offset = $start + strlen($placeholder);
    }
    return strtr($this->wp->sanitizeTextField($value), $tags);
  }
}
