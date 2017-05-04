<?php
use Codeception\Util\Stub;
use MailPoet\Config\Menu;
use MailPoet\Config\Renderer;
use MailPoet\Config\ServicesChecker;

class MenuTest extends MailPoetTest {
  function testItReturnsTrueIfCurrentPageBelongsToMailpoet() {
    $result = Menu::isOnMailPoetAdminPage(null, 'somepage');
    expect($result)->false();
    $result = Menu::isOnMailPoetAdminPage(null, 'mailpoet-newsletters');
    expect($result)->true();
  }

  function testItRespectsExclusionsWhenCheckingMPPages() {
    $exclude = array('mailpoet-welcome');
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
    $renderer = Stub::make(new Renderer());
    $assets_url = '';
    $menu = new Menu($renderer, $assets_url);

    $_REQUEST['page'] = 'mailpoet-newsletters';
    $checker = Stub::make(
      new ServicesChecker(),
      array('checkMailPoetAPIKeyValid' => true),
      $this
    );
    $menu->checkMailPoetAPIKey($checker);
    expect($menu->mp_api_key_valid)->true();

    $checker = Stub::make(
      new ServicesChecker(),
      array('checkMailPoetAPIKeyValid' => false),
      $this
    );
    $menu->checkMailPoetAPIKey($checker);
    expect($menu->mp_api_key_valid)->false();
  }

  function testItChecksPremiumKey() {
    $renderer = Stub::make(new Renderer());
    $assets_url = '';
    $menu = new Menu($renderer, $assets_url);

    $_REQUEST['page'] = 'mailpoet-newsletters';
    $checker = Stub::make(
      new ServicesChecker(),
      array('isPremiumKeyValid' => true),
      $this
    );
    $menu->checkPremiumKey($checker);
    expect($menu->premium_key_valid)->true();

    $checker = Stub::make(
      new ServicesChecker(),
      array('isPremiumKeyValid' => false),
      $this
    );
    $menu->checkPremiumKey($checker);
    expect($menu->premium_key_valid)->false();
  }
}