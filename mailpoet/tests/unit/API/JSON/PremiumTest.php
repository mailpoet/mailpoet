<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON;

use Codeception\Stub\Expected;
use MailPoet\API\JSON\ErrorResponse;
use MailPoet\API\JSON\SuccessResponse;
use MailPoet\API\JSON\v1\Premium;
use MailPoet\Config\Installer;
use MailPoet\Config\ServicesChecker;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WPCOM\DotcomHelperFunctions;

class PremiumTest extends \MailPoetUnitTest {
  public function testItInstallsPlugin() {
    $servicesChecker = $this->makeEmpty(ServicesChecker::class, [
      'isPremiumKeyValid' => Expected::once(true),
    ]);

    $wp = $this->make(WPFunctions::class, [
      'installPlugin' => Expected::once(true),
      'applyFilters' => function ($tag, $value) {
        return $value;
      },
    ]);

    $installer = $this->makeEmpty(Installer::class, [
      'generatePluginDownloadUrl' => Expected::once('https://example.com/premium.zip'),
    ]);

    $dotcom = $this->makeEmpty(DotcomHelperFunctions::class, [
      'isDotcom' => Expected::once(false),
    ]);

    $premium = new Premium($servicesChecker, $wp, $dotcom, $installer);
    $response = $premium->installPlugin();
    verify($response)->instanceOf(SuccessResponse::class);
  }

  public function testInstallationFailsWhenKeyInvalid() {
    $servicesChecker = $this->makeEmpty(ServicesChecker::class, [
      'isPremiumKeyValid' => Expected::once(false),
    ]);

    $wp = $this->makeEmpty(WPFunctions::class, [
      'applyFilters' => function ($tag, $value) {
        return $value;
      },
    ]);

    $installer = $this->makeEmpty(Installer::class);

    $premium = new Premium($servicesChecker, $wp, new DotcomHelperFunctions($wp), $installer);
    $response = $premium->installPlugin();
    verify($response)->instanceOf(ErrorResponse::class);
    verify($response->getData()['errors'][0])->same([
      'error' => 'bad_request',
      'message' => 'Premium key is not valid.',
    ]);
  }

  public function testInstallationFailsWhenNoPluginInfo() {
    $servicesChecker = $this->makeEmpty(ServicesChecker::class, [
      'isPremiumKeyValid' => Expected::once(true),
    ]);

    $wp = $this->make(WPFunctions::class, [
      'installPlugin' => Expected::once(false),
      'applyFilters' => function ($tag, $value) {
        return $value;
      },
    ]);

    $installer = $this->makeEmpty(Installer::class, [
      'generatePluginDownloadUrl' => Expected::once(''),
    ]);

    $premium = new Premium($servicesChecker, $wp, new DotcomHelperFunctions($wp), $installer);
    $response = $premium->installPlugin();
    verify($response)->instanceOf(ErrorResponse::class);
    verify($response->getData()['errors'][0])->same([
      'error' => 'bad_request',
      'message' => 'Error when installing MailPoet Premium plugin.',
    ]);
  }

  public function testInstallationFailsOnError() {
    $servicesChecker = $this->makeEmpty(ServicesChecker::class, [
      'isPremiumKeyValid' => Expected::once(true),
    ]);

    $wp = $this->make(WPFunctions::class, [
      'installPlugin' => Expected::once(false),
      'applyFilters' => function ($tag, $value) {
        return $value;
      },
    ]);

    $installer = $this->makeEmpty(Installer::class);

    $premium = new Premium($servicesChecker, $wp, new DotcomHelperFunctions($wp), $installer);
    $response = $premium->installPlugin();
    verify($response)->instanceOf(ErrorResponse::class);
    verify($response->getData()['errors'][0])->same([
      'error' => 'bad_request',
      'message' => 'Error when installing MailPoet Premium plugin.',
    ]);
  }

  public function testItActivatesPlugin() {
    $servicesChecker = $this->makeEmpty(ServicesChecker::class, [
      'isPremiumKeyValid' => Expected::once(true),
    ]);

    $wp = $this->make(WPFunctions::class, [
      'activatePlugin' => Expected::once(null),
      'applyFilters' => function ($tag, $value) {
        return $value;
      },
    ]);

    $installer = $this->makeEmpty(Installer::class);

    $premium = new Premium($servicesChecker, $wp, new DotcomHelperFunctions($wp), $installer);
    $response = $premium->activatePlugin();
    verify($response)->instanceOf(SuccessResponse::class);
  }

  public function testActivationFailsWhenKeyInvalid() {
    $servicesChecker = $this->makeEmpty(ServicesChecker::class, [
      'isPremiumKeyValid' => Expected::once(false),
    ]);

    $wp = $this->makeEmpty(WPFunctions::class, [
      'applyFilters' => function ($tag, $value) {
        return $value;
      },
    ]);
    $installer = $this->makeEmpty(Installer::class);

    $premium = new Premium($servicesChecker, $wp, new DotcomHelperFunctions($wp), $installer);
    $response = $premium->activatePlugin();
    verify($response)->instanceOf(ErrorResponse::class);
    verify($response->getData()['errors'][0])->same([
      'error' => 'bad_request',
      'message' => 'Premium key is not valid.',
    ]);
  }

  public function testActivationFailsOnError() {
    $servicesChecker = $this->makeEmpty(ServicesChecker::class, [
      'isPremiumKeyValid' => Expected::once(true),
    ]);

    $wp = $this->make(WPFunctions::class, [
      'activatePlugin' => Expected::once('error'),
      'applyFilters' => function ($tag, $value) {
        return $value;
      },
    ]);

    $installer = $this->makeEmpty(Installer::class);

    $premium = new Premium($servicesChecker, $wp, new DotcomHelperFunctions($wp), $installer);
    $response = $premium->activatePlugin();
    verify($response)->instanceOf(ErrorResponse::class);
    verify($response->getData()['errors'][0])->same([
      'error' => 'bad_request',
      'message' => 'Error when activating MailPoet Premium plugin.',
    ]);
  }
}
