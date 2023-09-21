<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use Codeception\Util\Stub;
use MailPoet\Config\AccessControl;
use MailPoet\Config\Menu;
use MailPoet\Config\Router;
use MailPoet\Config\ServicesChecker;
use MailPoet\Form\Util\CustomFonts;
use MailPoet\WP\Functions as WPFunctions;

class MenuTest extends \MailPoetTest {
  public function testItReturnsTrueIfCurrentPageBelongsToMailpoet() {
    $result = Menu::isOnMailPoetAdminPage(null, 'somepage');
    expect($result)->false();
    $result = Menu::isOnMailPoetAdminPage(null, 'mailpoet-newsletters');
    expect($result)->true();
  }

  public function testItRespectsExclusionsWhenCheckingMPPages() {
    $exclude = ['mailpoet-welcome'];
    $result = Menu::isOnMailPoetAdminPage($exclude, 'mailpoet-welcome');
    expect($result)->false();
    $result = Menu::isOnMailPoetAdminPage($exclude, 'mailpoet-newsletters');
    expect($result)->true();
  }

  public function testItWorksWithRequestDataWhenCheckingMPPages() {
    $_REQUEST['page'] = 'mailpoet-newsletters';
    $result = Menu::isOnMailPoetAdminPage();
    expect($result)->true();

    $_REQUEST['page'] = 'blah';
    $result = Menu::isOnMailPoetAdminPage();
    expect($result)->false();

    unset($_REQUEST['page']);
    $result = Menu::isOnMailPoetAdminPage();
    expect($result)->false();
  }

  public function testItChecksPremiumKey() {
    $menu = $this->diContainer->get(Menu::class);

    $_REQUEST['page'] = 'mailpoet-newsletters';
    $checker = Stub::make(
      new ServicesChecker(),
      ['isPremiumKeyValid' => true],
      $this
    );
    $menu->checkPremiumKey($checker);
    expect($menu->premiumKeyValid)->true();

    $checker = Stub::make(
      new ServicesChecker(),
      ['isPremiumKeyValid' => false],
      $this
    );
    $menu->checkPremiumKey($checker);
    expect($menu->premiumKeyValid)->false();
  }

  public function testItHidesAutomationIfBundledSubscriptionAndAutomateWooActive() {
    $checker = Stub::make(
      new ServicesChecker(),
      [
        'isPremiumKeyValid' => true,
        'isBundledSubscription' => true,
      ],
      $this
    );

    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->method('isPluginActive')->willReturn(true);

    $accessControlMock = $this->createMock(AccessControl::class);
    $accessControlMock->method('validatePermission')->willReturn(true);

    $wpMock->expects($this->any())->method('addSubmenuPage')->withConsecutive(
      [$this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything()],
      [$this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything()],
      [$this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything()],
      [$this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything()],
      [$this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything()],
      [null, $this->anything(), $this->anything(), $this->anything(), Menu::AUTOMATIONS_PAGE_SLUG, $this->anything()]
    )->willReturn(true);

    $menu = new Menu(
      $accessControlMock,
      $wpMock,
      $checker,
      $this->diContainer,
      $this->diContainer->get(Router::class),
      $this->diContainer->get(CustomFonts::class)
    );

    $menu->setup();

  }
}
