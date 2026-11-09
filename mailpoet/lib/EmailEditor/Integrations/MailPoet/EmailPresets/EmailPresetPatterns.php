<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\WP\Functions as WPFunctions;

/**
 * Shows saved presets in the editor's "Start with an email preset" modal.
 * They are registered as block patterns hidden from the block inserter.
 */
class EmailPresetPatterns {
  const CATEGORY = 'mailpoet-saved-presets';
  const NAME_PREFIX = 'mailpoet/saved-preset-';
  // The modal renders a live preview of each preset, so it can't show many of them.
  const LIMIT = 100;

  private WPFunctions $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function initialize(): void {
    // The editor loads patterns over REST, so other requests don't need to query presets.
    $this->wp->addAction('rest_api_init', [$this, 'registerPatterns']);
    $this->wp->addFilter('rest_request_after_callbacks', [$this, 'moveSavedPresetsFirst'], 10, 3);
  }

  public function registerPatterns(): void {
    $presets = $this->wp->getPosts([
      'post_type' => EmailPresetPostType::NAME,
      'post_status' => EmailPresetPostType::STATUS,
      'numberposts' => self::LIMIT,
      'orderby' => 'modified',
      'order' => 'DESC',
    ]);
    $presets = array_filter($presets, function ($preset): bool {
      return $preset instanceof \WP_Post && trim($preset->post_content) !== ''; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    });
    if (!$presets) {
      return;
    }

    $this->wp->registerBlockPatternCategory(self::CATEGORY, [
      'label' => _x('Your saved presets', 'Block pattern category', 'mailpoet'),
      'description' => __('Emails you saved as presets.', 'mailpoet'),
    ]);

    foreach ($presets as $preset) {
      $this->wp->registerBlockPattern(self::NAME_PREFIX . $preset->ID, [
        'title' => $preset->post_title ?: __('Untitled preset', 'mailpoet'), // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        'description' => (string)$this->wp->getPostMeta($preset->ID, EmailPresetPostType::META_DESCRIPTION, true),
        'content' => $preset->post_content, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        'categories' => [self::CATEGORY],
        'templateTypes' => ['email-template'],
        'postTypes' => EmailEditor::EDITOR_POST_TYPES,
        'inserter' => false,
      ]);
    }
  }

  /**
   * The modal orders its tabs by the order of the patterns, so saved presets
   * go first to follow the "Recent" tab.
   *
   * @param \WP_REST_Response|\WP_HTTP_Response|\WP_Error|mixed $response
   * @return \WP_REST_Response|\WP_HTTP_Response|\WP_Error|mixed
   */
  public function moveSavedPresetsFirst($response, $handler, \WP_REST_Request $request) {
    if ($request->get_route() !== '/wp/v2/block-patterns/patterns' || !$response instanceof \WP_REST_Response) {
      return $response;
    }
    $patterns = $response->get_data();
    if (!is_array($patterns)) {
      return $response;
    }

    $savedPresets = [];
    $others = [];
    foreach ($patterns as $pattern) {
      $name = is_array($pattern) && is_string($pattern['name'] ?? null) ? $pattern['name'] : '';
      if (strpos($name, self::NAME_PREFIX) === 0) {
        $savedPresets[] = $pattern;
      } else {
        $others[] = $pattern;
      }
    }
    if ($savedPresets) {
      $response->set_data(array_merge($savedPresets, $others));
    }
    return $response;
  }
}
