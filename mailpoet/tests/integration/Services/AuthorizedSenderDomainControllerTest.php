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
  }

  public function testItFetchSenderDomains() {
    $domains = ['mailpoet.com', 'good', 'testdomain.com'];
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
      'getTransient' => $oldBridgeResponse,
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
      'getTransient' => $oldBridgeResponse,
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
      ['status' => 'valid'],
    ]];

    $domainsRawData = [
      'testdomain.com' => [
        'domain' => 'testdomain.com',
        'domain_status' => 'verified',
        'dns' => [],
      ],
    ];

    $getSenderDomainsExpectation = Expected::once($domains);
    $getSenderDomainsRawDataExpectation = Expected::once($domainsRawData);
    $verifySenderDomainsExpectation = Expected::never();

    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedSenderDomains' => $getSenderDomainsExpectation,
      'getRawSenderDomainData' => $getSenderDomainsRawDataExpectation,
      'verifyAuthorizedSenderDomain' => $verifySenderDomainsExpectation,
    ]);
    $controller = $this->getController($bridgeMock);
    $controller->verifyAuthorizedSenderDomain('testdomain.com');
  }

  public function testVerifyAuthorizedSenderDomainVerifiesPartiallyVerifiedDomains() {
    $domains = ['testdomain.com' => [
      ['status' => 'valid'],
      ['status' => 'valid'],
      ['status' => 'valid'],
      ['status' => 'pending'],
    ]];

    $domainsRawData = [
      'testdomain.com' => [
        'domain' => 'testdomain.com',
        'domain_status' => 'partially_verified',
        'dns' => [],
      ],
    ];

    $response = [
      'status' => API::RESPONSE_STATUS_OK,
      'dns' => [],
    ];

    $getSenderDomainsExpectation = Expected::once($domains);
    $getSenderDomainsRawDataExpectation = Expected::exactly(2, $domainsRawData);
    $verifySenderDomainsExpectation = Expected::once($response);

    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedSenderDomains' => $getSenderDomainsExpectation,
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

    $domains = ['testdomain.com' => []];
    $domainsRawData = [
      'testdomain.com' => [
        'domain' => 'testdomain.com',
        'domain_status' => 'unverified',
        'dns' => [],
      ],
    ];
    $getSenderDomainsExpectation = Expected::once($domains);
    $getSenderDomainsRawDataExpectation = Expected::once($domainsRawData);
    $verifySenderDomainsExpectation = Expected::once([
      'error' => $errorMessage,
      'message' => $errorMessage,
      'status' => API::RESPONSE_STATUS_ERROR,
    ]);

    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedSenderDomains' => $getSenderDomainsExpectation,
      'getRawSenderDomainData' => $getSenderDomainsRawDataExpectation,
      'verifyAuthorizedSenderDomain' => $verifySenderDomainsExpectation,
    ]);
    $controller = $this->getController($bridgeMock);
    $controller->verifyAuthorizedSenderDomain('testdomain.com');
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
      'getSubscribersCount' => Expected::once($this->lowerLimit + 1),
    ]);

    $this->assertTrue($this->getController(null, $subscribersMock)->isAuthorizedDomainRequiredForNewCampaigns());
  }

  public function testIsAuthorizedDomainRequiredForExistingCampaigns(): void {
    // Is new user
    $this->settings->set('version', MAILPOET_VERSION);
    $this->settings->set('installed_after_new_domain_restrictions', '1');

    // Is Big Sender
    $subscribersMock = $this->make(Subscribers::class, [
      'getSubscribersCount' => Expected::once($this->upperLimit + 1),
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
      'getSubscribersCount' => Expected::once($this->lowerLimit + 1),
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
}
