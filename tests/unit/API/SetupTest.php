<?php
use \MailPoet\API\Response;
use \MailPoet\API\Endpoints\Setup;
use \MailPoet\Models\Setting;

class SetupTest extends MailPoetTest {
  function _before() {
    Setting::setValue('signup_confirmation.enabled', false);
  }

  function testItCanReinstall() {
    $router = new Setup();
    $response = $router->reset();
    expect($response->status)->equals(Response::STATUS_OK);

    $signup_confirmation = Setting::getValue('signup_confirmation.enabled');
    expect($signup_confirmation)->true();
  }

  function _after() {
    Setting::deleteMany();
  }
}