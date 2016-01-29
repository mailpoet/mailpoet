<?php
use \MailPoet\Router\Setup;
use \MailPoet\Models\Setting;

class SetupCest {
  function _before() {
    Setting::setValue('signup_confirmation.enabled', false);
  }

  function itCanReinstall() {
    $router = new Setup();
    $response = $router->reset();
    expect($response['result'])->true();

    $signup_confirmation = Setting::getValue('signup_confirmation.enabled');
    expect($signup_confirmation)->true();
  }

  function _after() {
    ORM::forTable(Setting::$_table)->deleteMany();
  }
}