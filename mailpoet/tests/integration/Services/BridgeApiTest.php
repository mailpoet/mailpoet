<?php declare(strict_types = 1);

namespace MailPoet\Services\Bridge;

use MailPoet\Entities\LogEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Logging\LogRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;

require_once('BridgeTestMockAPI.php');

class BridgeApiTest extends \MailPoetTest {

  /** @var API */
  private $api;

  /** @var WPFunctions & MockObject */
  private $wpMock;

  /** @var LogRepository */
  private $logRepository;

  public function _before() {
    parent::_before();
    $this->wpMock = $this->createMock(WPFunctions::class);
    // This is to ensure that the logger is recreated with the new logging level
    LoggerFactory::getInstance()->clearLoggerInstances();
    (SettingsController::getInstance())->set('logging', 'everything');
    $this->api = new API('test-api-key', $this->wpMock);
    $this->logRepository = $this->diContainer->get(LogRepository::class);
  }

  public function _after() {
    parent::_after();
    // Clear the logger instances for next tests
    LoggerFactory::getInstance()->clearLoggerInstances();
    (SettingsController::getInstance())->set('logging', 'errors');
  }

  public function testItDoesntLogsWhenPremiumKeyCheckPass() {
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(200);
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveBody')
      ->willReturn('');
    $this->api->checkPremiumKey();
    $logs = $this->logRepository->findAll();
    verify($logs)->arrayCount(0);
  }

  public function testItLogsWhenPremiumKeyCheckFails() {
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(401);
    $this->wpMock
      ->expects($this->once())
      ->method('homeUrl')
      ->willReturn('www.home-example.com');
    $this->api->checkPremiumKey();
    $logs = $this->logRepository->findAll();
    verify($logs)->arrayCount(1);
    $errorLog = $logs[0];
    $this->assertInstanceOf(LogEntity::class, $errorLog);
    verify($errorLog->getLevel())->equals(Logger::INFO);
    verify($errorLog->getMessage())->stringContainsString('premium.INFO:');
    verify($errorLog->getMessage())->stringContainsString('www.home-example.com');
    verify($errorLog->getMessage())->stringContainsString('key-validation.failed');
    verify($errorLog->getMessage())->stringContainsString('"key_type":"premium"');
  }

  public function testItDoesntLogsWhenMssKeyCheckPass() {
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(200);
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveBody')
      ->willReturn('');
    $this->api->checkMSSKey();
    $logs = $this->logRepository->findAll();
    verify($logs)->arrayCount(0);
  }

  public function testItLogsWhenMssKeyCheckFails() {
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(401);
    $this->wpMock
      ->expects($this->once())
      ->method('homeUrl')
      ->willReturn('www.home-example.com');
    $this->api->checkMSSKey();
    $logs = $this->logRepository->findAll();
    verify($logs)->arrayCount(1);
    $errorLog = $logs[0];
    $this->assertInstanceOf(LogEntity::class, $errorLog);
    verify($errorLog->getLevel())->equals(Logger::INFO);
    verify($errorLog->getMessage())->stringContainsString('mss.INFO:');
    verify($errorLog->getMessage())->stringContainsString('www.home-example.com');
    verify($errorLog->getMessage())->stringContainsString('key-validation.failed');
    verify($errorLog->getMessage())->stringContainsString('"key_type":"mss"');
  }

  public function testItCanGetSenderDomains() {
    $domainResult = BridgeTestMockAPI::VERIFIED_DOMAIN_RESPONSE;
    $domainResult['domain'] = 'mailpoet.com';
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(200);
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveBody')
      ->willReturn(json_encode([$domainResult]));
    $result = $this->api->getAuthorizedSenderDomains();
    verify($result)->equals([$domainResult]);
  }

  public function testItReturnsNullIfCantGetSenderDomains() {
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(400);
    $result = $this->api->getAuthorizedSenderDomains();
    verify($result)->null();
  }

  public function testGetDomainsLogsErrorWhenResponseHasUnexpectedFormat() {
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(200);
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveBody')
      ->willReturn('trololo');
    $this->api->getAuthorizedSenderDomains();
    $logs = $this->logRepository->findAll();
    verify($logs)->arrayCount(1);
    $errorLog = $logs[0];
    $this->assertInstanceOf(LogEntity::class, $errorLog);
    verify($errorLog->getLevel())->equals(Logger::ERROR);
    verify($errorLog->getMessage())->stringContainsString('getAuthorizedSenderDomains API response was not in expected format.');
    verify($errorLog->getMessage())->stringContainsString('trololo');
  }

  public function testItCanCreateSenderDomain() {
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(201);
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveBody')
      ->willReturn(json_encode(BridgeTestMockAPI::VERIFIED_DOMAIN_RESPONSE));
    $result = $this->api->createAuthorizedSenderDomain('mailpoet.com');
    verify($result)->equals(BridgeTestMockAPI::VERIFIED_DOMAIN_RESPONSE);
  }

  public function testCreateDomainLogsErrorWhenResponseHasUnexpectedFormat() {
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(201);
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveBody')
      ->willReturn('trololo');
    $result = $this->api->createAuthorizedSenderDomain('mailpoet.com');
    verify($result)->equals([]);
    $logs = $this->logRepository->findAll();
    verify($logs)->arrayCount(1);
    $errorLog = $logs[0];
    $this->assertInstanceOf(LogEntity::class, $errorLog);
    verify($errorLog->getLevel())->equals(Logger::ERROR);
    verify($errorLog->getMessage())->stringContainsString('createAuthorizedSenderDomain API response was not in expected format.');
    verify($errorLog->getMessage())->stringContainsString('trololo');
  }

  public function testCantCreateSenderDomainWhichExists() {
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(403);
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveBody')
      ->willReturn(json_encode(['error' => 'This domain was already added to the list.']));
    $result = $this->api->createAuthorizedSenderDomain('existing.com');
    verify($result['status'])->equals(API::RESPONSE_STATUS_ERROR);
    verify($result['error'])->equals('This domain was already added to the list.');
  }

  public function testItFetchesBouncesReportWithIsoUtcRange() {
    $from = new \DateTimeImmutable('2026-06-15 23:59:59', new \DateTimeZone('UTC'));
    $to = new \DateTimeImmutable('2026-06-16 23:59:59', new \DateTimeZone('UTC'));
    // The endpoint returns recipients as {email, type} objects; getBouncesReport
    // flattens them to plain email addresses.
    $wireReport = ['recipients' => [['email' => 'bob@example.com', 'type' => 'hard']], 'page' => 2, 'has_more' => false];
    $flattenedReport = ['recipients' => ['bob@example.com'], 'page' => 2, 'has_more' => false];
    $this->wpMock
      ->expects($this->once())
      ->method('addQueryArg')
      ->with(
        [
          'from' => '2026-06-15T23:59:59Z',
          'to' => '2026-06-16T23:59:59Z',
          'p' => 2,
        ],
        $this->api->urlBouncesReport
      )
      ->willReturn('https://bridge.example/report');
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(200);
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveBody')
      ->willReturn((string)json_encode($wireReport));
    verify($this->api->getBouncesReport($from, $to, 2))->equals($flattenedReport);
  }

  public function testItConvertsBouncesReportRangeToUtc() {
    $from = new \DateTimeImmutable('2026-06-16 01:59:59', new \DateTimeZone('+02:00'));
    $to = new \DateTimeImmutable('2026-06-17 01:59:59', new \DateTimeZone('+02:00'));
    $this->wpMock
      ->expects($this->once())
      ->method('addQueryArg')
      ->with(
        [
          'from' => '2026-06-15T23:59:59Z',
          'to' => '2026-06-16T23:59:59Z',
          'p' => 1,
        ],
        $this->api->urlBouncesReport
      )
      ->willReturn('https://bridge.example/report');
    $this->wpMock
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(200);
    $this->wpMock
      ->method('wpRemoteRetrieveBody')
      ->willReturn((string)json_encode(['recipients' => [], 'page' => 1, 'has_more' => false]));
    $this->api->getBouncesReport($from, $to);
  }

  public function testItReturnsNullWhenBouncesReportRequestFails() {
    $from = new \DateTimeImmutable('2026-06-15 23:59:59', new \DateTimeZone('UTC'));
    $to = new \DateTimeImmutable('2026-06-16 23:59:59', new \DateTimeZone('UTC'));
    $this->wpMock
      ->method('addQueryArg')
      ->willReturn('https://bridge.example/report');
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(500);
    verify($this->api->getBouncesReport($from, $to))->null();
  }

  public function testItReturnsNullAndLogsWhenBouncesReportPayloadIsMalformed() {
    $from = new \DateTimeImmutable('2026-06-15 23:59:59', new \DateTimeZone('UTC'));
    $to = new \DateTimeImmutable('2026-06-16 23:59:59', new \DateTimeZone('UTC'));
    $this->wpMock
      ->method('addQueryArg')
      ->willReturn('https://bridge.example/report');
    $this->wpMock
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(200);
    // Valid JSON, but missing has_more: a 200 must not be treated as a complete
    // empty page that advances the report window.
    $this->wpMock
      ->method('wpRemoteRetrieveBody')
      ->willReturn((string)json_encode(['recipients' => [['email' => 'bob@example.com', 'type' => 'hard']], 'page' => 1]));

    verify($this->api->getBouncesReport($from, $to))->null();

    $logs = $this->logRepository->findAll();
    verify($logs)->arrayCount(1);
    $errorLog = $logs[0];
    $this->assertInstanceOf(LogEntity::class, $errorLog);
    verify($errorLog->getLevel())->equals(Logger::ERROR);
    verify($errorLog->getMessage())->stringContainsString('getBouncesReport API response was not in expected format.');
  }

  public function testItRejectsBouncesReportRecipientsWithoutAnEmail() {
    $from = new \DateTimeImmutable('2026-06-15 23:59:59', new \DateTimeZone('UTC'));
    $to = new \DateTimeImmutable('2026-06-16 23:59:59', new \DateTimeZone('UTC'));
    $this->wpMock
      ->method('addQueryArg')
      ->willReturn('https://bridge.example/report');
    $this->wpMock
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(200);
    // A recipient missing the email key (e.g. the pre-{email,type} string shape)
    // must be rejected rather than silently dropped.
    $this->wpMock
      ->method('wpRemoteRetrieveBody')
      ->willReturn((string)json_encode(['recipients' => ['bob@example.com'], 'page' => 1, 'has_more' => false]));

    verify($this->api->getBouncesReport($from, $to))->null();
  }

  public function testVerifyDomainLogsErrorWhenResponseHasUnexpectedFormat() {
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(200);
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveBody')
      ->willReturn('trololo');
    $result = $this->api->verifyAuthorizedSenderDomain('mailpoet.com');
    verify($result)->equals([]);
    $logs = $this->logRepository->findAll();
    verify($logs)->arrayCount(1);
    $errorLog = $logs[0];
    $this->assertInstanceOf(LogEntity::class, $errorLog);
    verify($errorLog->getLevel())->equals(Logger::ERROR);
    verify($errorLog->getMessage())->stringContainsString('verifyAuthorizedSenderDomain API response was not in expected format.');
    verify($errorLog->getMessage())->stringContainsString('trololo');
  }

  public function testItSendsRenderedMessagesToTheMessagesEndpoint() {
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemotePost')
      ->with($this->equalTo($this->api->urlMessages))
      ->willReturn([]);
    $this->wpMock
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(201);

    $result = $this->api->sendMessages([
      ['to' => ['address' => 'john@example.com', 'name' => 'John'], 'subject' => 'Hello'],
    ]);
    verify($result['status'])->equals(API::RESPONSE_STATUS_OK);
  }

  public function testItSendsTemplateBatchesToTheTemplateMessagesEndpoint() {
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemotePost')
      ->with($this->equalTo($this->api->urlTemplateMessages))
      ->willReturn([]);
    $this->wpMock
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(201);

    $result = $this->api->sendMessages([
      'format' => API::SENDING_FORMAT_TEMPLATE_BATCH,
      'template' => ['subject' => 'Hello {{mailpoet_mss_1}}'],
      'messages' => [
        ['to' => ['address' => 'john@example.com', 'name' => 'John'], 'substitutions' => ['{{mailpoet_mss_1}}' => 'John']],
      ],
    ]);
    verify($result['status'])->equals(API::RESPONSE_STATUS_OK);
  }
}
