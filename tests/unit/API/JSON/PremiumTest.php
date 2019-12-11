<?php

namespace MailPoet\Test\API\JSON;

use Codeception\Stub\Expected;
use MailPoet\API\JSON\ErrorResponse;
use MailPoet\API\JSON\SuccessResponse;
use MailPoet\API\JSON\v1\Premium;
use MailPoet\Config\ServicesChecker;
use MailPoet\WP\Functions as WPFunctions;

class PremiumTest extends \MailPoetUnitTest {
  public function testItInstallsPlugin() {
    $services_checker = $this->makeEmpty(ServicesChecker::class, [
      'isPremiumKeyValid' => Expected::once(true),
    ]);

    $wp = $this->make(WPFunctions::class, [
      'pluginsApi' => Expected::once([
        'download_link' => 'https://some-download-link',
      ]),
      'installPlugin' => Expected::once(true),
    ]);

    $premium = new Premium($services_checker, $wp);
    $response = $premium->installPlugin();
    expect($response)->isInstanceOf(SuccessResponse::class);
  }

  public function testInstallationFailsWhenKeyInvalid() {
    $services_checker = $this->makeEmpty(ServicesChecker::class, [
      'isPremiumKeyValid' => Expected::once(false),
    ]);

    $wp = $this->make(WPFunctions::class, [
      'pluginsApi' => Expected::never(),
      'installPlugin' => Expected::never(),
    ]);

    $premium = new Premium($services_checker, $wp);
    $response = $premium->installPlugin();
    expect($response)->isInstanceOf(ErrorResponse::class);
    expect($response->getData()['errors'][0])->same([
      'error' => 'bad_request',
      'message' => 'Premium key is not valid.',
    ]);
  }

  public function testInstallationFailsWhenNoPluginInfo() {
    $services_checker = $this->makeEmpty(ServicesChecker::class, [
      'isPremiumKeyValid' => Expected::once(true),
    ]);

    $wp = $this->make(WPFunctions::class, [
      'pluginsApi' => Expected::once(null),
      'installPlugin' => Expected::never(),
    ]);

    $premium = new Premium($services_checker, $wp);
    $response = $premium->installPlugin();
    expect($response)->isInstanceOf(ErrorResponse::class);
    expect($response->getData()['errors'][0])->same([
      'error' => 'bad_request',
      'message' => 'Error when installing MailPoet Premium plugin.',
    ]);
  }

  public function testInstallationFailsOnError() {
    $services_checker = $this->makeEmpty(ServicesChecker::class, [
      'isPremiumKeyValid' => Expected::once(true),
    ]);

    $wp = $this->make(WPFunctions::class, [
      'pluginsApi' => Expected::once([
        'download_link' => 'https://some-download-link',
      ]),
      'installPlugin' => Expected::once(false),
    ]);

    $premium = new Premium($services_checker, $wp);
    $response = $premium->installPlugin();
    expect($response)->isInstanceOf(ErrorResponse::class);
    expect($response->getData()['errors'][0])->same([
      'error' => 'bad_request',
      'message' => 'Error when installing MailPoet Premium plugin.',
    ]);
  }

  public function testItActivatesPlugin() {
    $services_checker = $this->makeEmpty(ServicesChecker::class, [
      'isPremiumKeyValid' => Expected::once(true),
    ]);

    $wp = $this->make(WPFunctions::class, [
      'activatePlugin' => Expected::once(null),
    ]);

    $premium = new Premium($services_checker, $wp);
    $response = $premium->activatePlugin();
    expect($response)->isInstanceOf(SuccessResponse::class);
  }

  public function testActivationFailsWhenKeyInvalid() {
    $services_checker = $this->makeEmpty(ServicesChecker::class, [
      'isPremiumKeyValid' => Expected::once(false),
    ]);

    $premium = new Premium($services_checker, new WPFunctions());
    $response = $premium->activatePlugin();
    expect($response)->isInstanceOf(ErrorResponse::class);
    expect($response->getData()['errors'][0])->same([
      'error' => 'bad_request',
      'message' => 'Premium key is not valid.',
    ]);
  }

  public function testActivationFailsOnError() {
    $services_checker = $this->makeEmpty(ServicesChecker::class, [
      'isPremiumKeyValid' => Expected::once(true),
    ]);

    $wp = $this->make(WPFunctions::class, [
      'activatePlugin' => Expected::once('error'),
    ]);

    $premium = new Premium($services_checker, $wp);
    $response = $premium->activatePlugin();
    expect($response)->isInstanceOf(ErrorResponse::class);
    expect($response->getData()['errors'][0])->same([
      'error' => 'bad_request',
      'message' => 'Error when activating MailPoet Premium plugin.',
    ]);
  }
}
