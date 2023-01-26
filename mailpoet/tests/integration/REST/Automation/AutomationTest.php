<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation;

require_once __DIR__ . '/../Test.php';

use MailPoet\REST\Test;

abstract class AutomationTest extends Test {

  protected $editorUserId;

  public function _before() {
    parent::_before();
    wp_set_current_user(1);
    $userId = wp_create_user(
      'editor',
      'password',
      'editor@localhost'
    );
    $this->assertIsNumeric($userId);
    $user = new \WP_User($userId);
    $user->add_role('editor');
    $this->editorUserId = $userId;
  }

  public function _after() {
    parent::_after();
    wp_set_current_user(0);
    is_multisite() ? wpmu_delete_user($this->editorUserId) : wp_delete_user($this->editorUserId);
  }
}
