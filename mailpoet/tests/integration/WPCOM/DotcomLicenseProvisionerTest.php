<?php declare(strict_types = 1);

namespace MailPoet\WPCOM;

use MailPoet\API\JSON\ErrorResponse;
use MailPoet\API\JSON\SuccessResponse;
use MailPoet\API\JSON\v1\Services;
use MailPoet\API\JSON\v1\Settings;
use MailPoet\Logging\LoggerFactory;

class DotcomLicenseProvisionerTest extends \MailPoetTest {
  /** @var DotcomLicenseProvisioner */
  private $provisioner;

  public function _before() {
    parent::_before();
    $this->provisioner = $this->construct(
      DotcomLicenseProvisioner::class,
      [
        $this->diContainer->get(LoggerFactory::class),
        $this->make(Settings::class),
        $this->make(Services::class),
        $this->make(DotcomHelperFunctions::class, ['isAtomicPlatform' => true]),
      ]);
  }

  public function testItReturnsResultIfNotAtomic() {
    $result = false;
    $payload = ['apiKey' => 'some-key'];
    $provisioner = $this->construct(
      DotcomLicenseProvisioner::class,
      [
        $this->make(LoggerFactory::class),
        $this->make(Settings::class),
        $this->make(Services::class),
        $this->make(DotcomHelperFunctions::class, ['isAtomicPlatform' => false]),
      ]);
    verify($provisioner->provisionLicense($result, $payload, DotcomLicenseProvisioner::EVENT_TYPE_PROVISION_LICENSE))->equals($result);
  }

  public function testItReturnsResultIfWrongEvent() {
    $result = false;
    $payload = ['apiKey' => 'some-key'];
    $eventType = 'wrong-event';
    verify($this->provisioner->provisionLicense($result, $payload, $eventType))->equals($result);
  }

  public function testItReturnsWPErrorIfMissingKey() {
    $result = false;
    $payload = [];
    $eventType = DotcomLicenseProvisioner::EVENT_TYPE_PROVISION_LICENSE;
    $error = $this->provisioner->provisionLicense($result, $payload, $eventType);
    $this->assertInstanceOf(\WP_Error::class, $error);
    verify($error->get_error_message())->equals('Invalid license payload: Missing API key.');
  }

  public function testItReturnsWPErrorIfErrorOnSettingUpMSS() {
    $result = false;
    $payload = ['apiKey' => 'some-key'];
    $eventType = DotcomLicenseProvisioner::EVENT_TYPE_PROVISION_LICENSE;
    $provisioner = $this->construct(
      DotcomLicenseProvisioner::class,
      [
        $this->diContainer->get(LoggerFactory::class),
        $this->make(Settings::class, ['setKeyAndSetupMss' => new ErrorResponse(['error' => 'some-error'])]),
        $this->make(Services::class, ['refreshMSSKeyStatus' => new SuccessResponse()]),
        $this->make(DotcomHelperFunctions::class, ['isAtomicPlatform' => true]),
      ]);
    $error = $provisioner->provisionLicense($result, $payload, $eventType);
    $this->assertInstanceOf(\WP_Error::class, $error);
    verify($error->get_error_message())->equals('some-error ');
  }

  public function testItReturnsErrorIfCouldNotRefreshKey() {
    $result = false;
    $payload = ['apiKey' => 'some-key'];
    $eventType = DotcomLicenseProvisioner::EVENT_TYPE_PROVISION_LICENSE;
    $provisioner = $this->construct(
      DotcomLicenseProvisioner::class,
      [
        $this->diContainer->get(LoggerFactory::class),
        $this->make(Settings::class, ['setKeyAndSetupMss' => new SuccessResponse()]),
        $this->make(Services::class, ['refreshMSSKeyStatus' => new ErrorResponse(['error' => 'some-error'])]),
        $this->make(DotcomHelperFunctions::class, ['isAtomicPlatform' => true]),
      ]);
    $error = $provisioner->provisionLicense($result, $payload, $eventType);
    $this->assertInstanceOf(\WP_Error::class, $error);
    verify($error->get_error_message())->equals('some-error ');
  }

  public function testItReturnsErrorIfCouldNotVerifyPremiumKey() {
    $result = false;
    $payload = ['apiKey' => 'some-key'];
    $eventType = DotcomLicenseProvisioner::EVENT_TYPE_PROVISION_LICENSE;
    $provisioner = $this->construct(
      DotcomLicenseProvisioner::class,
      [
        $this->diContainer->get(LoggerFactory::class),
        $this->make(Settings::class, ['setKeyAndSetupMss' => new SuccessResponse()]),
        $this->make(Services::class,
          [
            'refreshMSSKeyStatus' => new SuccessResponse(),
            'refreshPremiumKeyStatus' => new ErrorResponse(['error' => 'some-error']),
          ]),
        $this->make(DotcomHelperFunctions::class, ['isAtomicPlatform' => true]),
      ]);
    $error = $provisioner->provisionLicense($result, $payload, $eventType);
    $this->assertInstanceOf(\WP_Error::class, $error);
    verify($error->get_error_message())->equals('some-error ');
  }

  public function testItReturnsTrueIfKeyProvidedMSSActivatedAndRefreshed() {
    $result = false;
    $payload = ['apiKey' => 'some-key'];
    $eventType = DotcomLicenseProvisioner::EVENT_TYPE_PROVISION_LICENSE;
    $provisioner = $this->construct(
      DotcomLicenseProvisioner::class,
      [
        $this->diContainer->get(LoggerFactory::class),
        $this->make(Settings::class, ['setKeyAndSetupMss' => new SuccessResponse()]),
        $this->make(Services::class,
          [
            'refreshMSSKeyStatus' => new SuccessResponse(),
            'refreshPremiumKeyStatus' => new SuccessResponse(),
          ]),
        $this->make(DotcomHelperFunctions::class, ['isAtomicPlatform' => true]),
      ]);
    $result = $provisioner->provisionLicense($result, $payload, $eventType);
    verify($result)->equals(true);
  }
}
