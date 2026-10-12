<?php declare(strict_types = 1);

namespace MailPoet\Test\Services;

use Codeception\Stub\Expected;
use InvalidArgumentException;
use MailPoet\Mailer\Mailer;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Services\AuthorizedSenderDomainController;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\StatisticsNewsletters;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\Util\License\Features\Subscribers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

require_once('BridgeTestMockAPI.php');

class AuthorizedSenderDomainControllerTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  /** @var Bridge */
  private $bridge;

  private $apiKey;

  /**@var WPFunctions */
  private $wp;

  private int $lowerLimit;

  private int $upperLimit;

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
    $this->wp = $this->make(WPFunctions::class, [
      'getTransient' => false,
      'setTransient' => true,
    ]);

    $this->lowerLimit = AuthorizedSenderDomainController::LOWER_LIMIT;
    $this->upperLimit = AuthorizedSenderDomainController::UPPER_LIMIT;
    $this->deleteSenderDomainTransients();
  }

  public function _after() {
    $this->deleteSenderDomainTransients();
    parent::_after();
  }

  private function deleteSenderDomainTransients(): void {
    delete_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_KEY);
    delete_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_RELOAD_FAILED_KEY);
  }

  public function testItFetchSenderDomains() {
    $domains = ['mailpoet.com', 'good', 'testdomain.com'];
    $bridgeResponse = [
      ['domain' => 'mailpoet.com', 'domain_status' => 'verified', 'dns' => ['data']],
      ['domain' => 'good', 'domain_status' => 'verified', 'dns' => ['data']],
      ['domain' => 'testdomain.com', 'domain_status' => 'unverified', 'dns' => ['data']],
    ];

    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::once($bridgeResponse),
    ]);


    $controller = $this->getController($bridgeMock);
    $allDomains = $controller->getAllSenderDomains();
    verify($allDomains)->same($domains);
  }

  public function testItReturnsVerifiedSenderDomains() {
    $bridgeResponse = [
      [
        'domain' => 'mailpoet.com',
        'domain_status' => 'verified',
        'dns' => Bridge\BridgeTestMockAPI::VERIFIED_DOMAIN_RESPONSE['dns'],
        ],
      [
        'domain' => 'testdomain.com',
        'domain_status' => 'unverified',
        'dns' => [],
      ],
    ];

    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::once($bridgeResponse),
    ]);

    $controller = $this->getController($bridgeMock);
    $verifiedDomains = $controller->getVerifiedSenderDomains();
    verify($verifiedDomains)->same(['mailpoet.com']); // only this is Verified for now
  }

  public function testItReturnsVerifiedSenderDomainsFromTransient() {
    $bridgeResponse = [
      [
        'domain' => 'mailpoet.com',
        'domain_status' => 'verified',
        'dns' => Bridge\BridgeTestMockAPI::VERIFIED_DOMAIN_RESPONSE['dns'],
      ],
      [
        'domain' => 'testdomain.com',
        'domain_status' => 'unverified',
        'dns' => [],
      ],
    ];

    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::never(),
    ]);
    $this->wp = $this->make(WPFunctions::class, [
      'getTransient' => $bridgeResponse,
      'setTransient' => true,
    ]);

    $controller = $this->getController($bridgeMock);
    $verifiedDomains = $controller->getVerifiedSenderDomains();
    verify($verifiedDomains)->same(['mailpoet.com']);
  }

  public function testItReturnsVerifiedSenderDomainsIgnoringCache() {
    $oldBridgeResponse = [
      [
        'domain' => 'mailpoet.com',
        'domain_status' => 'verified',
        'dns' => Bridge\BridgeTestMockAPI::VERIFIED_DOMAIN_RESPONSE['dns'],
      ],
      [
        'domain' => 'testdomain.com',
        'domain_status' => 'unverified',
        'dns' => [],
      ],
    ];

    $newBridgeResponse = [
      [
        'domain' => 'mailpoet.com',
        'domain_status' => 'verified',
        'dns' => Bridge\BridgeTestMockAPI::VERIFIED_DOMAIN_RESPONSE['dns'],
      ],
      [
        'domain' => 'testdomain.com',
        'domain_status' => 'verified',
        'dns' => [],
      ],
    ];

    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::once($newBridgeResponse),
    ]);
    $this->wp = $this->make(WPFunctions::class, [
      'getTransient' => function($key) use ($oldBridgeResponse) {
        return $key === AuthorizedSenderDomainController::SENDER_DOMAINS_KEY ? $oldBridgeResponse : false;
      },
      'setTransient' => true,
    ]);

    $controller = $this->getController($bridgeMock);
    $verifiedDomains = $controller->getVerifiedSenderDomainsIgnoringCache();
    verify($verifiedDomains)->same(['mailpoet.com', 'testdomain.com']);
    // Reset mock
    $this->wp = $this->make(WPFunctions::class, [
      'getTransient' => false,
      'setTransient' => true,
    ]);
  }

  public function testItReturnsEmptyArrayWhenNoVerifiedSenderDomains() {
    $expectation = Expected::once([]); // with empty array

    $bridgeMock = $this->make(Bridge::class, ['getRawSenderDomainData' => $expectation]);
    $controller = $this->getController($bridgeMock);

    $verifiedDomains = $controller->getVerifiedSenderDomains();
    verify($verifiedDomains)->same([]);

    $domains = [
      [
        'domain' => 'testdomain.com',
        'domain_status' => 'unverified',
        'dns' => [],
      ],
    ];
    $expectation = Expected::once($domains);

    $bridgeMock = $this->make(Bridge::class, ['getRawSenderDomainData' => $expectation]);
    $controller = $this->getController($bridgeMock);
    $verifiedDomains = $controller->getVerifiedSenderDomains();
    verify($verifiedDomains)->same([]);
  }

  public function testItUsesTransientWhenBridgeReturnsNoData() {
    $oldBridgeResponse = [
      [
        'domain' => 'mailpoet.com',
        'domain_status' => 'verified',
        'dns' => Bridge\BridgeTestMockAPI::VERIFIED_DOMAIN_RESPONSE['dns'],
      ],
      [
        'domain' => 'testdomain.com',
        'domain_status' => 'unverified',
        'dns' => [],
      ],
    ];

    $newBridgeResponse = null;


    $bridgeMock = $this->createMock(Bridge::class);
    $bridgeMock->expects($this->exactly(2))
      ->method('getRawSenderDomainData')
      ->willReturnOnConsecutiveCalls($oldBridgeResponse, $newBridgeResponse);

    $this->wp = $this->make(WPFunctions::class, [
      'getTransient' => function($key) use ($oldBridgeResponse) {
        return $key === AuthorizedSenderDomainController::SENDER_DOMAINS_KEY ? $oldBridgeResponse : false;
      },
      'setTransient' => true,
    ]);

    $controller = $this->getController($bridgeMock);
    $verifiedDomains = $controller->getVerifiedSenderDomainsIgnoringCache();
    verify($verifiedDomains)->same(['mailpoet.com']);
    // Second call
    $verifiedDomains = $controller->getVerifiedSenderDomainsIgnoringCache();
    verify($verifiedDomains)->same(['mailpoet.com']);
    // Reset mock
    $this->wp = $this->make(WPFunctions::class, [
      'getTransient' => false,
      'setTransient' => true,
    ]);
  }

  public function testCreateAuthorizedSenderDomainThrowsForExistingDomains() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Sender domain exist');

    $domainsRawData = [['domain' => 'testdomain.com', 'domain_status' => 'unverified', 'dns' => []]];
    $createSenderDomainsExpectation = Expected::never();

    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::once($domainsRawData),
      'createAuthorizedSenderDomain' => $createSenderDomainsExpectation,
    ]);
    $controller = $this->getController($bridgeMock);
    $controller->createAuthorizedSenderDomain('testdomain.com');
  }

  public function testVerifyAuthorizedSenderDomainThrowsForNoneExistingDomains() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Sender domain does not exist');

    $domainsRawData = [['domain' => 'newdomain.com', 'domain_status' => 'unverified', 'dns' => []]];
    $verifySenderDomainsExpectation = Expected::never();

    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::once($domainsRawData),
      'verifyAuthorizedSenderDomain' => $verifySenderDomainsExpectation,
    ]);
    $controller = $this->getController($bridgeMock);
    $controller->verifyAuthorizedSenderDomain('testdomain.com');
  }

  public function testVerifyAuthorizedSenderDomainThrowsForVerifiedDomains() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Sender domain already verified');

    $domainsRawData = [
      [
        'domain' => 'testdomain.com',
        'domain_status' => 'verified',
        'dns' => [],
      ],
    ];

    $getSenderDomainsRawDataExpectation = Expected::once($domainsRawData);
    $verifySenderDomainsExpectation = Expected::never();

    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => $getSenderDomainsRawDataExpectation,
      'verifyAuthorizedSenderDomain' => $verifySenderDomainsExpectation,
    ]);
    $controller = $this->getController($bridgeMock);
    $controller->verifyAuthorizedSenderDomain('testdomain.com');
  }

  public function testVerifyAuthorizedSenderDomainVerifiesPartiallyVerifiedDomains() {
    $domainsRawData = [
      [
        'domain' => 'testdomain.com',
        'domain_status' => 'partially_verified',
        'dns' => [],
      ],
    ];

    $response = [
      'status' => API::RESPONSE_STATUS_OK,
      'dns' => [],
    ];

    $getSenderDomainsRawDataExpectation = Expected::exactly(2, $domainsRawData);
    $verifySenderDomainsExpectation = Expected::once($response);

    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => $getSenderDomainsRawDataExpectation,
      'verifyAuthorizedSenderDomain' => $verifySenderDomainsExpectation,
    ]);
    $controller = $this->getController($bridgeMock);
    $controller->verifyAuthorizedSenderDomain('testdomain.com');
  }

  public function testItCanGetDomainsByOverallStatus(): void {
    $verifiedDomain = [
      'domain' => 'example1.com',
      'domain_status' => 'verified',
      'dns' => [],
    ];
    $partiallyVerifiedDomain = [
      'domain' => 'example2.com',
      'domain_status' => 'partially-verified',
      'dns' => [],
    ];
    $unverifiedDomain = [
      'domain' => 'example3.com',
      'domain_status' => 'unverified',
      'dns' => [],
    ];

    $mockResponse = [
      $verifiedDomain,
      $partiallyVerifiedDomain,
      $unverifiedDomain,
    ];

    $getRawSenderDomainData = Expected::once($mockResponse);

    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => $getRawSenderDomainData,
    ]);

    $controller = $this->getController($bridgeMock);

    $domainsByStatus = $controller->getSenderDomainsByStatus(['verified']);
    $this->assertEqualsCanonicalizing([$verifiedDomain], $domainsByStatus);

    $domainsByStatus = $controller->getSenderDomainsByStatus(['partially-verified']);
    $this->assertEqualsCanonicalizing([$partiallyVerifiedDomain], $domainsByStatus);

    $domainsByStatus = $controller->getSenderDomainsByStatus(['unverified']);
    $this->assertEqualsCanonicalizing([$unverifiedDomain], $domainsByStatus);

    $grouped = $controller->getSenderDomainsGroupedByStatus();
    $this->assertEqualsCanonicalizing([
      'verified' => [$verifiedDomain],
      'partially-verified' => [$partiallyVerifiedDomain],
      'unverified' => [$unverifiedDomain],
    ], $grouped);

    $domains = $controller->getFullyVerifiedSenderDomains(true);
    $this->assertEqualsCanonicalizing(['example1.com'], $domains);

    $domains = $controller->getPartiallyVerifiedSenderDomains(true);
    $this->assertEqualsCanonicalizing(['example2.com'], $domains);

    $domains = $controller->getFullyOrPartiallyVerifiedSenderDomains(true);
    $this->assertEqualsCanonicalizing(['example1.com', 'example2.com'], $domains);

    $domains = $controller->getUnverifiedSenderDomains(true);
    $this->assertEqualsCanonicalizing(['example3.com'], $domains);
  }

  public function testVerifyAuthorizedSenderDomainThrowsForOtherErrors() {
    $errorMessage = 'This is a test message';
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage($errorMessage);

    $domainsRawData = [
      [
        'domain' => 'testdomain.com',
        'domain_status' => 'unverified',
        'dns' => [],
      ],
    ];
    $getSenderDomainsRawDataExpectation = Expected::once($domainsRawData);
    $verifySenderDomainsExpectation = Expected::once([
      'error' => $errorMessage,
      'message' => $errorMessage,
      'status' => API::RESPONSE_STATUS_ERROR,
    ]);

    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => $getSenderDomainsRawDataExpectation,
      'verifyAuthorizedSenderDomain' => $verifySenderDomainsExpectation,
    ]);
    $controller = $this->getController($bridgeMock);
    $controller->verifyAuthorizedSenderDomain('testdomain.com');
  }

  public function testItCachesEmptyDomainListAndCallsBridgeOnce(): void {
    $this->wp = new WPFunctions();
    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::once([]),
    ]);
    $controller = $this->getController($bridgeMock);

    verify($controller->getVerifiedSenderDomains())->same([]);
    verify($controller->getPartiallyVerifiedSenderDomains(true))->same([]);
    verify($controller->getAllSenderDomains())->same([]);
    verify($controller->isCacheAvailable())->true();
    verify(get_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_KEY))->same([]);
    verify($controller->isLastReloadFailed())->false();
  }

  public function testFailedReloadCallsBridgeOncePerRequestEvenWithoutPersistentBackoff(): void {
    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::once(null),
    ]);
    $controller = $this->getController($bridgeMock);

    verify($controller->getVerifiedSenderDomains())->same([]);
    verify($controller->getAllSenderDomains())->same([]);
    verify($controller->getSenderDomainsGroupedByStatus())->same([]);
  }

  public function testItDoesNotCacheFailedReloadAndSetsBackoff(): void {
    $this->wp = new WPFunctions();
    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::once(null),
    ]);
    $controller = $this->getController($bridgeMock);

    verify($controller->getVerifiedSenderDomains())->same([]);
    verify($controller->getAllSenderDomains())->same([]);
    verify($controller->getDomainRecords())->same([]);
    verify($controller->getSenderDomainsGroupedByStatus())->same([]);
    verify($controller->isLastReloadFailed())->true();
    verify(get_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_KEY))->false();
    verify(get_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_RELOAD_FAILED_KEY))->notEmpty();
  }

  public function testFailedReloadDoesNotOverwriteExistingTransient(): void {
    $cached = [['domain' => 'mailpoet.com', 'domain_status' => 'verified', 'dns' => []]];
    set_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_KEY, $cached, 3600);
    $this->wp = new WPFunctions();
    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::once(null),
    ]);
    $controller = $this->getController($bridgeMock);

    verify($controller->getVerifiedSenderDomainsIgnoringCache())->same(['mailpoet.com']);
    verify(get_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_KEY))->same($cached);
    verify($controller->isLastReloadFailed())->true();
  }

  public function testItSkipsBridgeWhileReloadFailureBackoffIsActive(): void {
    set_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_RELOAD_FAILED_KEY, 1, 300);
    $this->wp = new WPFunctions();
    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::never(),
    ]);
    $controller = $this->getController($bridgeMock);

    verify($controller->getVerifiedSenderDomainsIgnoringCache())->same([]);
    verify($controller->getAllSenderDomains())->same([]);
    verify($controller->isLastReloadFailed())->true();
  }

  public function testForcedActionsIgnoreReloadFailureBackoff(): void {
    set_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_RELOAD_FAILED_KEY, 1, 300);
    $this->wp = new WPFunctions();
    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::exactly(2, [['domain' => 'other.com', 'domain_status' => 'unverified', 'dns' => []]]),
      'createAuthorizedSenderDomain' => Expected::once(['status' => API::RESPONSE_STATUS_OK, 'dns' => []]),
    ]);
    $controller = $this->getController($bridgeMock);

    $controller->createAuthorizedSenderDomain('new.com');
    verify(get_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_RELOAD_FAILED_KEY))->false();
  }

  public function testItUsesEmptyTransientWithoutCallingBridge(): void {
    set_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_KEY, [], 3600);
    $this->wp = new WPFunctions();
    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::never(),
    ]);
    $controller = $this->getController($bridgeMock);

    verify($controller->getAllSenderDomains())->same([]);
    verify($controller->getVerifiedSenderDomains())->same([]);
    verify($controller->isCacheAvailable())->true();
  }

  public function testItDerivesDomainRecordsFromCachedRows(): void {
    $dns = [['type' => 'TXT', 'host' => 'a', 'value' => 'b']];
    set_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_KEY, [
      ['domain' => 'mailpoet.com', 'domain_status' => 'verified', 'dns' => $dns],
    ], 3600);
    $this->wp = new WPFunctions();
    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::never(),
    ]);
    $controller = $this->getController($bridgeMock);

    verify($controller->getDomainRecords('MailPoet.com'))->same($dns);
    verify($controller->getDomainRecords('missing.com'))->same([]);
    verify($controller->getDomainRecords())->same(['mailpoet.com' => $dns]);
  }

  public function testCreateReloadsBeforeExistenceCheck(): void {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Sender domain exist');
    $this->wp = new WPFunctions();
    set_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_KEY, [], 3600);
    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::once([['domain' => 'testdomain.com', 'domain_status' => 'unverified', 'dns' => []]]),
      'createAuthorizedSenderDomain' => Expected::never(),
    ]);
    $this->getController($bridgeMock)->createAuthorizedSenderDomain('TestDomain.com');
  }

  public function testCreateCallsBridgeWhenReloadFails(): void {
    $this->wp = new WPFunctions();
    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::atLeastOnce(null),
      'createAuthorizedSenderDomain' => Expected::once(['status' => API::RESPONSE_STATUS_OK, 'dns' => []]),
    ]);
    $controller = $this->getController($bridgeMock);
    $controller->createAuthorizedSenderDomain('new.com');
    verify(get_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_KEY))->false();
  }

  public function testVerifyCallsBridgeWhenReloadFails(): void {
    $this->wp = new WPFunctions();
    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::atLeastOnce(null),
      'verifyAuthorizedSenderDomain' => Expected::once(['status' => API::RESPONSE_STATUS_OK, 'dns' => []]),
    ]);
    $response = $this->getController($bridgeMock)->verifyAuthorizedSenderDomain('new.com');
    verify($response['status'])->same(API::RESPONSE_STATUS_OK);
  }

  public function testClearCacheDeletesTransientsAndRefetches(): void {
    set_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_KEY, [], 3600);
    set_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_RELOAD_FAILED_KEY, 1, 300);
    $this->wp = new WPFunctions();
    $bridgeMock = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::once([['domain' => 'a.com', 'domain_status' => 'verified', 'dns' => []]]),
    ]);
    $controller = $this->getController($bridgeMock);
    verify($controller->getAllSenderDomains())->same([]);

    $controller->clearCache();

    verify(get_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_KEY))->false();
    verify(get_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_RELOAD_FAILED_KEY))->false();
    verify($controller->getAllSenderDomains())->same(['a.com']);
  }

  public function testItCanRewriteEmailAddresses(): void {
    $email = 'jane.doe@gmail.com';
    $this->assertSame('jane.doe=gmail.com@replies.sendingservice.net', $this->getController()->getRewrittenEmailAddress($email));
  }

  private function getController($bridgeMock = null, $subscribersMock = null): AuthorizedSenderDomainController {
    $newsletterStatisticsRepository = $this->diContainer->get(NewsletterStatisticsRepository::class);
    $subscribers = $this->diContainer->get(Subscribers::class);
    return new AuthorizedSenderDomainController(
      $bridgeMock ?? $this->bridge,
      $newsletterStatisticsRepository,
      $this->settings,
      $subscribersMock ?? $subscribers,
      $this->wp
    );
  }

  public function testUserIsNewIfTheyHaveNotCompletedWelcomeWizard(): void {
    $this->settings->delete('version');
    $this->assertTrue($this->getController()->isNewUser());
  }

  public function testUserIsNewIfTheyInstalledAfterNewRestrictionsImplemented(): void {
    $this->settings->set('version', MAILPOET_VERSION);
    $this->settings->set('installed_after_new_domain_restrictions', '1');
    $this->assertTrue($this->getController()->isNewUser());
    // also true even if they've sent emails
    (new StatisticsNewsletters((new Newsletter())->withSendingQueue()->create(), (new Subscriber())->create()))->create();
    $this->assertTrue($this->getController()->isNewUser());
  }

  public function testUserIsNotNewIfTheyDoNotHaveTheSettingAndHaveSentEmails(): void {
    $this->settings->set('version', MAILPOET_VERSION);
    $this->settings->delete('installed_after_new_domain_restrictions');
    // No emails yet, so they are still "new"
    $this->assertTrue($this->getController()->isNewUser());
    (new StatisticsNewsletters((new Newsletter())->withSendingQueue()->create(), (new Subscriber())->create()))->create();
    $this->assertFalse($this->getController()->isNewUser());
  }

  public function testIsSmallSenderIfSubscribersUnderLowerLimit(): void {
    $subscribersMock = $this->make(Subscribers::class, [
      'getSubscribersCount' => Expected::once($this->lowerLimit),
    ]);

    $this->assertTrue($this->getController(null, $subscribersMock)->isSmallSender());
  }

  public function testIsNotSmallSenderIfSubscribersOverLowerLimit(): void {
    $subscribersMock = $this->make(Subscribers::class, [
      'getSubscribersCount' => Expected::once($this->lowerLimit + 1),
    ]);

    $this->assertFalse($this->getController(null, $subscribersMock)->isSmallSender());
  }

  public function testIsAuthorizedDomainRequiredForNewCampaigns(): void {
    // Is new user
    $this->settings->set('version', MAILPOET_VERSION);
    $this->settings->set('installed_after_new_domain_restrictions', '1');

    // Is not small sender
    $subscribersMock = $this->make(Subscribers::class, [
      'getSubscribersCount' => $this->lowerLimit + 1,
    ]);

    $this->assertTrue($this->getController(null, $subscribersMock)->isAuthorizedDomainRequiredForNewCampaigns());
  }

  public function testIsAuthorizedDomainRequiredForExistingCampaigns(): void {
    // Is new user
    $this->settings->set('version', MAILPOET_VERSION);
    $this->settings->set('installed_after_new_domain_restrictions', '1');

    // Is Big Sender
    $subscribersMock = $this->make(Subscribers::class, [
      'getSubscribersCount' => $this->upperLimit + 1,
    ]);

    $this->assertTrue($this->getController(null, $subscribersMock)->isAuthorizedDomainRequiredForExistingCampaigns());
  }

  public function testNotIsAuthorizedDomainRequiredForNewCampaignsForExistingUsersBeforeEnforcementDate(): void {
    // Is not new user
    $this->settings->set('version', MAILPOET_VERSION);
    $this->settings->delete('installed_after_new_domain_restrictions');
    (new StatisticsNewsletters((new Newsletter())->withSendingQueue()->create(), (new Subscriber())->create()))->create();

    // Before EnforcementDate
    Carbon::setTestNow(Carbon::parse('2024-01-31 00:00:00 UTC'));

    $this->assertFalse($this->getController()->isAuthorizedDomainRequiredForNewCampaigns());
  }

  public function testIsAuthorizedDomainRequiredForNewCampaignsForExistingUsersAfterEnforcementDate(): void {
    // Is not new user
    $this->settings->set('version', MAILPOET_VERSION);
    $this->settings->delete('installed_after_new_domain_restrictions');
    (new StatisticsNewsletters((new Newsletter())->withSendingQueue()->create(), (new Subscriber())->create()))->create();

    // After EnforcementDate
    Carbon::setTestNow(Carbon::parse('2024-02-01 00:00:01 UTC'));

    // Is not small sender
    $subscribersMock = $this->make(Subscribers::class, [
      'getSubscribersCount' => $this->lowerLimit + 1,
    ]);

    $this->assertTrue($this->getController(null, $subscribersMock)->isAuthorizedDomainRequiredForNewCampaigns());
  }

  public function testNotIsAuthorizedDomainRequiredForNewCampaignsForSmallSenders(): void {
    // Is not new user
    $this->settings->set('version', MAILPOET_VERSION);
    $this->settings->delete('installed_after_new_domain_restrictions');
    (new StatisticsNewsletters((new Newsletter())->withSendingQueue()->create(), (new Subscriber())->create()))->create();

    // After EnforcementDate
    Carbon::setTestNow(Carbon::parse('2024-02-01 00:00:01 UTC'));

    // Is small sender
    $subscribersMock = $this->make(Subscribers::class, [
      'getSubscribersCount' => Expected::once($this->lowerLimit),
    ]);

    $this->assertFalse($this->getController(null, $subscribersMock)->isAuthorizedDomainRequiredForNewCampaigns());
  }

  public function testNotIsAuthorizedDomainRequiredForNewCampaignsForNewUsersSmallSenders(): void {
    // Is new user
    $this->settings->set('version', MAILPOET_VERSION);
    $this->settings->set('installed_after_new_domain_restrictions', '1');

    // Is small sender
    $subscribersMock = $this->make(Subscribers::class, [
      'getSubscribersCount' => Expected::once($this->lowerLimit),
    ]);

    $this->assertFalse($this->getController(null, $subscribersMock)->isAuthorizedDomainRequiredForNewCampaigns());
  }

  public function testIsBundledSubscription(): void {
    $this->settings->set(Bridge::SUBSCRIPTION_TYPE_SETTING_NAME, Bridge::WPCOM_BUNDLE_SUBSCRIPTION_TYPE);
    $this->assertTrue($this->getController()->isBundledSubscription());
  }

  public function testIsNotBundledSubscriptionForOtherTypes(): void {
    $this->settings->set(Bridge::SUBSCRIPTION_TYPE_SETTING_NAME, 'MANUAL');
    $this->assertFalse($this->getController()->isBundledSubscription());
  }

  public function testShouldSkipAuthorizationForBundledSmallSender(): void {
    $this->settings->set(Bridge::SUBSCRIPTION_TYPE_SETTING_NAME, Bridge::WPCOM_BUNDLE_SUBSCRIPTION_TYPE);
    $subscribersMock = $this->make(Subscribers::class, [
      'getSubscribersCount' => $this->lowerLimit,
    ]);
    $this->assertTrue($this->getController(null, $subscribersMock)->shouldSkipAuthorization());
  }

  public function testShouldNotSkipAuthorizationForBundledBigSender(): void {
    $this->settings->set(Bridge::SUBSCRIPTION_TYPE_SETTING_NAME, Bridge::WPCOM_BUNDLE_SUBSCRIPTION_TYPE);
    $subscribersMock = $this->make(Subscribers::class, [
      'getSubscribersCount' => $this->upperLimit + 1,
    ]);
    $this->assertFalse($this->getController(null, $subscribersMock)->shouldSkipAuthorization());
  }

  public function testShouldNotSkipAuthorizationForNonBundledSmallSender(): void {
    $this->settings->set(Bridge::SUBSCRIPTION_TYPE_SETTING_NAME, 'MANUAL');
    $subscribersMock = $this->make(Subscribers::class, [
      'getSubscribersCount' => $this->lowerLimit,
    ]);
    $this->assertFalse($this->getController(null, $subscribersMock)->shouldSkipAuthorization());
  }

  public function testNotIsAuthorizedDomainRequiredForNewCampaignsForBundledSmallSender(): void {
    $this->settings->set(Bridge::SUBSCRIPTION_TYPE_SETTING_NAME, Bridge::WPCOM_BUNDLE_SUBSCRIPTION_TYPE);
    $subscribersMock = $this->make(Subscribers::class, [
      'getSubscribersCount' => $this->lowerLimit,
    ]);
    $this->assertFalse($this->getController(null, $subscribersMock)->isAuthorizedDomainRequiredForNewCampaigns());
  }

  public function testNotIsAuthorizedDomainRequiredForExistingCampaignsForBundledSmallSender(): void {
    $this->settings->set(Bridge::SUBSCRIPTION_TYPE_SETTING_NAME, Bridge::WPCOM_BUNDLE_SUBSCRIPTION_TYPE);
    $subscribersMock = $this->make(Subscribers::class, [
      'getSubscribersCount' => $this->lowerLimit,
    ]);
    $this->assertFalse($this->getController(null, $subscribersMock)->isAuthorizedDomainRequiredForExistingCampaigns());
  }

  public function testIsAuthorizedDomainRequiredForBundledBigSender(): void {
    $this->settings->set(Bridge::SUBSCRIPTION_TYPE_SETTING_NAME, Bridge::WPCOM_BUNDLE_SUBSCRIPTION_TYPE);
    $subscribersMock = $this->make(Subscribers::class, [
      'getSubscribersCount' => $this->upperLimit + 1,
    ]);
    $this->assertTrue($this->getController(null, $subscribersMock)->isAuthorizedDomainRequiredForNewCampaigns());
    // Need a fresh mock since count is called again
    $subscribersMock2 = $this->make(Subscribers::class, [
      'getSubscribersCount' => $this->upperLimit + 1,
    ]);
    $this->assertTrue($this->getController(null, $subscribersMock2)->isAuthorizedDomainRequiredForExistingCampaigns());
  }
}
