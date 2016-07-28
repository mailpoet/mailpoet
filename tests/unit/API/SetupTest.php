<?php
use \MailPoet\API
    \Setup;
use \MailPoet\Models\Setting;

class SetupTest extends MailPoetTest {
  function _before() {
    Setting::setValue('signup_confirmation.enabled', false);
  }

  function testItCanReinstall() {
    /*$router = new Setup();
    $response = $router->reset();
    expect($response['result'])->true();

    $signup_confirmation = Setting::getValue('signup_confirmation.enabled');
    expect($signup_confirmation)->true();*/
  }

  function _after() {
    Setting::deleteMany();
  }
}