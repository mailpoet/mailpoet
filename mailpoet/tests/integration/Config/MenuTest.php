<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use Codeception\Util\Stub;
use MailPoet\Config\Menu;
use MailPoet\Config\ServicesChecker;

class MenuTest extends \MailPoetTest {
  public function testItReturnsTrueIfCurrentPageBelongsToMailpoet() {
    $result = Menu::isOnMailPoetAdminPage(null, 'somepage');
    verify($result)->false();
    $result = Menu::isOnMailPoetAdminPage(null, 'mailpoet-newsletters');
    verify($result)->true();
  }

  public function testItRespectsExclusionsWhenCheckingMPPages() {
    $exclude = ['mailpoet-welcome'];
    $result = Menu::isOnMailPoetAdminPage($exclude, 'mailpoet-welcome');
    verify($result)->false();
    $result = Menu::isOnMailPoetAdminPage($exclude, 'mailpoet-newsletters');
    verify($result)->true();
  }

  public function testItWorksWithRequestDataWhenCheckingMPPages() {
    $_REQUEST['page'] = 'mailpoet-newsletters';
    $result = Menu::isOnMailPoetAdminPage();
    verify($result)->true();

    $_REQUEST['page'] = 'blah';
    $result = Menu::isOnMailPoetAdminPage();
    verify($result)->false();

    unset($_REQUEST['page']);
    $result = Menu::isOnMailPoetAdminPage();
    verify($result)->false();
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
    verify($menu->premiumKeyValid)->true();

    $checker = Stub::make(
      new ServicesChecker(),
      ['isPremiumKeyValid' => false],
      $this
    );
    $menu->checkPremiumKey($checker);
    verify($menu->premiumKeyValid)->false();
  }
}
