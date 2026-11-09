<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Endpoints;

use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\Endpoint;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Config\AccessControl;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\EmailPresetPostType;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\EmailPresetsController;
use MailPoet\WP\Functions as WPFunctions;

abstract class EmailPresetsEndpoint extends Endpoint {
  protected EmailPresetsController $controller;

  protected WPFunctions $wp;

  public function __construct(
    EmailPresetsController $controller,
    WPFunctions $wp
  ) {
    $this->controller = $controller;
    $this->wp = $wp;
  }

  public function checkPermissions(): bool {
    return $this->wp->currentUserCan(AccessControl::PERMISSION_MANAGE_EMAILS);
  }

  protected function getPreset(Request $request, bool $includeTrashed = false): \WP_Post {
    $id = $request->getParam('id');
    $preset = is_numeric($id) ? $this->controller->getPreset((int)$id, $includeTrashed) : null;
    if (!$preset) {
      throw new ApiException(__('The email preset does not exist.', 'mailpoet'), 404, 'mailpoet_email_preset_not_found');
    }
    return $preset;
  }

  protected function getTitle(Request $request): string {
    $title = $request->getParam('title');
    $title = is_string($title) ? $this->wp->sanitizeTextField($title) : '';
    if ($title === '') {
      throw new ApiException(__('The preset name is required.', 'mailpoet'), 400, 'mailpoet_email_preset_title_required');
    }
    return $title;
  }

  protected function presetResponse(int $presetId, bool $includeTrashed = false): Response {
    $preset = $this->controller->getPreset($presetId, $includeTrashed);
    if (!$preset) {
      throw new ApiException(__('The email preset does not exist.', 'mailpoet'), 404, 'mailpoet_email_preset_not_found');
    }
    return new Response($this->buildItem($preset));
  }

  /** @return array<string, mixed> */
  protected function buildItem(\WP_Post $preset): array {
    $author = $this->wp->getUserdata((int)$preset->post_author); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    return [
      'id' => $preset->ID,
      'title' => $preset->post_title, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      'status' => $preset->post_status, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      'description' => (string)$this->wp->getPostMeta($preset->ID, EmailPresetPostType::META_DESCRIPTION, true),
      'subject' => (string)$this->wp->getPostMeta($preset->ID, EmailPresetPostType::META_SUBJECT, true),
      'preheader' => (string)$this->wp->getPostMeta($preset->ID, EmailPresetPostType::META_PREHEADER, true),
      'author' => $author instanceof \WP_User ? $author->display_name : '', // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      'updated_at' => $preset->post_modified_gmt, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      'edit_url' => $this->controller->getEditUrl($preset->ID),
    ];
  }
}
