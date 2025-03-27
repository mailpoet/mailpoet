<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Util;

use MailPoet\WP\Functions as WPFunctions;

if (!class_exists('\WP_REST_Posts_Controller')) {
  require_once ABSPATH . '/wp-includes/rest-api/endpoints/class-wp-rest-controller.php';
  require_once ABSPATH . '/wp-includes/rest-api/endpoints/class-wp-rest-posts-controller.php';
}

class APIPermissionHelper extends \WP_REST_Posts_Controller {
  /** @var WPFunctions */
  private $wp;

  public function __construct(
    ?WPFunctions $wp = null
  ) {
    // constructor is needed to override parent constructor
    $this->wp = $wp ?: new WPFunctions();
  }

  /**
   * Checks if current user has permission to read a post or product
   *
   * @param \WP_Post|\WC_Product $post Post or product to check
   * @return bool Whether the current user can read the post or product
   */
  public function checkReadPermission($post): bool {
    // Handle WooCommerce products
    if (class_exists('\WC_Product') && $post instanceof \WC_Product) {
      $status = $post->get_status();
      // Published products are readable by anyone
      if ($status === 'publish') {
        return true;
      }

      // Private products are readable by editors and admins
      if ($status === 'private' && $this->wp->currentUserCan('edit_private_posts')) {
        return true;
      }

      // Draft, pending, etc. are only readable by admins
      if (in_array($status, ['draft', 'pending']) && $this->wp->currentUserCan('read_private_posts') && $this->wp->currentUserCan('edit_others_posts')) {
        return true;
      }

      return false;
    }

    // Handle regular WordPress posts
    if ($post instanceof \WP_Post) {
      return parent::check_read_permission($post);
    }

    return false;
  }

  /**
   * Checks if a given post type can be viewed or managed.
   * Refrain from checking `show_in_rest` contrary to what parent::check_is_post_type_allowed does
   *
   * @param \WP_Post_Type|string $post_type Post type name or object.
   * @return bool Whether the post type is allowed in REST.
   * @see parent::check_is_post_type_allowed
   */
  // phpcs:disable PSR1.Methods.CamelCapsMethodName
  protected function check_is_post_type_allowed($post_type) {
    if (!is_object($post_type)) {
      $post_type = get_post_type_object($post_type);
    }

    return !empty($post_type) && $post_type->public;
  }
}
