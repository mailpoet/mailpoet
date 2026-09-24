<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine;

use MailPoet\Automation\Engine\WordPress;

class WordPressTest extends \MailPoetTest {
  public function testItReturnsEditableRolesOutsideAdminScreens(): void {
    $wordPress = $this->diContainer->get(WordPress::class);
    verify($wordPress->getEditableRoles())->arrayHasKey('subscriber');
  }
}
