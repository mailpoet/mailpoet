<?php declare(strict_types = 1);

namespace MailPoet\Test\Segments;

//phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps, PSR1.Methods.CamelCapsMethodName.NotCamelCaps

class WPTestUser extends \WP_User {
  /** @var int */
  public $orderId;

  /**
   * The native \WP_User::add_role() method contains the 'add_role' hook, which triggers
   * MailPoet to synchronize the user. Therefore, we overwrite the method here
   * for cases, where we do not want to trigger the synchronization but just want to
   * assign a role to a user.
   */
  public function add_role($role): void {
    if (empty($role)) {
      return;
    }

    $this->caps[$role] = true;
    update_user_meta($this->ID, $this->cap_key, $this->caps);
    $this->get_role_caps();
    $this->update_user_level_from_caps();
  }
}
