<?php declare(strict_types = 1);

namespace MailPoet\Test\Services;

use Codeception\Stub\Expected;
use InvalidArgumentException;
use MailPoet\Services\AuthorizedSenderDomainController;
use MailPoet\Util\DmarcPolicyChecker;
use MailPoet\Mailer\Mailer;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

require_once('BridgeTestMockAPI.php');

class AuthorizedSenderDomainControllerTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  /** @var Bridge */
  private $bridge;

  private $apiKey;

  public function _before() {
    parent::_before();

    $this->apiKey = getenv('WP_TEST_MAILER_MAILPOET_API');

    $this->bridge = new Bridge();
    $this->bridge->api = new API($this->apiKey, new WPFunctions());

    $this->settings = SettingsController::getInstance();
    $this->settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      [
        'method' => 'MailPoet',
        'mailpoet_api_key' => $this->apiKey,
      ]
    );
  }

  public function testItFetchSenderDomains() {
    $domains =  ['mailpoet.com', 'good', 'testdomain.com'];
    $bridgeResponse = [
      'mailpoet.com' => ['data'],
      'good' => ['data'],
      'testdomain.com' => ['data'],
    ];

    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedSenderDomains' => Expected::once($bridgeResponse),
    ]);


    $controller = $this->getController($bridgeMock);
    $allDomains = $controller->getAllSenderDomains();
    expect($allDomains)->same($domains);
  }

  public function testItReturnsVerifiedSenderDomains() {
    $bridgeResponse = [
      'mailpoet.com' => Bridge\BridgeTestMockAPI::VERIFIED_DOMAIN_RESPONSE['dns'],
      'good' => ['data'],
      'testdomain.com' => ['data'],
    ];

    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedSenderDomains' => Expected::once($bridgeResponse),
    ]);

    $controller = $this->getController($bridgeMock);
    $verifiedDomains = $controller->getVerifiedSenderDomains();
    expect($verifiedDomains)->same(['mailpoet.com']); // only this is Verified for now
  }

  public function testItReturnsEmptyArrayWhenNoVerifiedSenderDomains() {
    $expectation = Expected::once([]); // with empty array

    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedSenderDomains' => $expectation]);
    $controller = $this->getController($bridgeMock);

    $verifiedDomains = $controller->getVerifiedSenderDomains();
    expect($verifiedDomains)->same([]);

    $domains = ['testdomain.com' => []];
    $expectation = Expected::once($domains);

    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedSenderDomains' => $expectation]);
    $controller = $this->getController($bridgeMock);
    $verifiedDomains = $controller->getVerifiedSenderDomains();
    expect($verifiedDomains)->same([]);
  }

  public function testCreateAuthorizedSenderDomainThrowsForExistingDomains() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Sender domain exist');

    $domains = ['testdomain.com' => []];
    $getSenderDomainsExpectation = Expected::once($domains);
    $createSenderDomainsExpectation = Expected::never();

    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedSenderDomains' => $getSenderDomainsExpectation,
      'createAuthorizedSenderDomain' => $createSenderDomainsExpectation,
    ]);
    $controller = $this->getController($bridgeMock);
    $controller->createAuthorizedSenderDomain('testdomain.com');
  }

  public function testVerifyAuthorizedSenderDomainThrowsForNoneExistingDomains() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Sender domain does not exist');

    $domains = ['newdomain.com' => []];
    $getSenderDomainsExpectation = Expected::once($domains);
    $verifySenderDomainsExpectation = Expected::never();

    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedSenderDomains' => $getSenderDomainsExpectation,
      'verifyAuthorizedSenderDomain' => $verifySenderDomainsExpectation,
    ]);
    $controller = $this->getController($bridgeMock);
    $controller->verifyAuthorizedSenderDomain('testdomain.com');
  }

  public function testVerifyAuthorizedSenderDomainThrowsForVerifiedDomains() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Sender domain already verified');

    $domains = ['testdomain.com' => [
      ['status' => 'valid'],
      ['status' => 'valid'],
      ['status' => 'valid'],
    ]];
    $getSenderDomainsExpectation = Expected::once($domains);
    $verifySenderDomainsExpectation = Expected::never();

    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedSenderDomains' => $getSenderDomainsExpectation,
      'verifyAuthorizedSenderDomain' => $verifySenderDomainsExpectation,
    ]);
    $controller = $this->getController($bridgeMock);
    $controller->verifyAuthorizedSenderDomain('testdomain.com');
  }

  public function testVerifyAuthorizedSenderDomainThrowsForOtherErrors() {
    $errorMessage = 'This is a test message';
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage($errorMessage);

    $domains = ['testdomain.com' => []];
    $getSenderDomainsExpectation = Expected::once($domains);
    $verifySenderDomainsExpectation = Expected::once(['error' => $errorMessage, 'status' => false]);

    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedSenderDomains' => $getSenderDomainsExpectation,
      'verifyAuthorizedSenderDomain' => $verifySenderDomainsExpectation,
    ]);
    $controller = $this->getController($bridgeMock);
    $controller->verifyAuthorizedSenderDomain('testdomain.com');
  }

  public function testItReturnsTrueWhenDmarcIsEnabled() {
    $controller = $this->getController();
    $isRestricted = $controller->isDomainDmarcRestricted('mailpoet.com');
    expect($isRestricted)->same(true);
  }

  public function testItReturnsFalseWhenDmarcIsNotEnabled() {
    $controller = $this->getController();
    $isRestricted = $controller->isDomainDmarcRestricted('example.com');
    expect($isRestricted)->same(false);
  }

  public function testItReturnsDmarcStatus() {
    $controller = $this->getController();
    $isRestricted = $controller->getDmarcPolicyForDomain('example.com');
    expect($isRestricted)->same('none');
  }

  private function getController($bridgeMock = null): AuthorizedSenderDomainController {
    $dmarcPolicyChecker = $this->diContainer->get(DmarcPolicyChecker::class);
    return new AuthorizedSenderDomainController($bridgeMock ?? $this->bridge, $dmarcPolicyChecker);
  }
}
