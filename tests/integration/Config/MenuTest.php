<?php

namespace MailPoet\Test\Config;

use Codeception\Util\Stub;
use MailPoet\Config\AccessControl;
use MailPoet\Config\Menu;
use MailPoet\Config\ServicesChecker;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Features\FeatureFlagsRepository;
use MailPoet\Features\FeaturesController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class MenuTest extends \MailPoetTest {
  function testItReturnsTrueIfCurrentPageBelongsToMailpoet() {
    $result = Menu::isOnMailPoetAdminPage(null, 'somepage');
    expect($result)->false();
    $result = Menu::isOnMailPoetAdminPage(null, 'mailpoet-newsletters');
    expect($result)->true();
  }

  function testItRespectsExclusionsWhenCheckingMPPages() {
    $exclude = ['mailpoet-welcome'];
    $result = Menu::isOnMailPoetAdminPage($exclude, 'mailpoet-welcome');
    expect($result)->false();
    $result = Menu::isOnMailPoetAdminPage($exclude, 'mailpoet-newsletters');
    expect($result)->true();
  }

  function testItWorksWithRequestDataWhenCheckingMPPages() {
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

  function testItChecksMailpoetAPIKey() {
    $menu = $this->getMenu();

    $_REQUEST['page'] = 'mailpoet-newsletters';
    $checker = Stub::make(
      new ServicesChecker(),
      ['isMailPoetAPIKeyValid' => true],
      $this
    );
    $menu->checkMailPoetAPIKey($checker);
    expect($menu->mp_api_key_valid)->true();

    $checker = Stub::make(
      new ServicesChecker(),
      ['isMailPoetAPIKeyValid' => false],
      $this
    );
    $menu->checkMailPoetAPIKey($checker);
    expect($menu->mp_api_key_valid)->false();
  }

  function testItChecksPremiumKey() {
    $menu = $this->getMenu();

    $_REQUEST['page'] = 'mailpoet-newsletters';
    $checker = Stub::make(
      new ServicesChecker(),
      ['isPremiumKeyValid' => true],
      $this
    );
    $menu->checkPremiumKey($checker);
    expect($menu->premium_key_valid)->true();

    $checker = Stub::make(
      new ServicesChecker(),
      ['isPremiumKeyValid' => false],
      $this
    );
    $menu->checkPremiumKey($checker);
    expect($menu->premium_key_valid)->false();
  }

  private function getMenu() {
    $wp = new WPFunctions;
    return new Menu(
      new AccessControl(),
      $wp,
      new ServicesChecker,
      new FeaturesController(new FeatureFlagsRepository(ContainerWrapper::getInstance()->get(EntityManager::class))),
      ContainerWrapper::getInstance()
    );
  }
}
