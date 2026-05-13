<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter\Embed;

use Codeception\Stub;
use MailPoet\Newsletter\Embed\NewsletterEmbedService;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterEmbedServiceTest extends \MailPoetUnitTest {
  public function testItSanitizesAttributes(): void {
    $service = $this->createService();

    $this->assertSame([
      'newsletterId' => 0,
      'height' => NewsletterEmbedService::DEFAULT_HEIGHT,
      'showFallbackLink' => true,
      'align' => '',
    ], $service->sanitizeAttributes([]));

    $this->assertSame(123, $service->sanitizeAttributes(['newsletterId' => '123'])['newsletterId']);
    $this->assertSame(0, $service->sanitizeAttributes(['newsletterId' => '-123'])['newsletterId']);
    $this->assertSame(0, $service->sanitizeAttributes(['newsletterId' => 'abc'])['newsletterId']);

    $this->assertSame(NewsletterEmbedService::DEFAULT_HEIGHT, $service->sanitizeAttributes(['height' => ''])['height']);
    $this->assertSame(NewsletterEmbedService::DEFAULT_HEIGHT, $service->sanitizeAttributes(['height' => 'abc'])['height']);
    $this->assertSame(NewsletterEmbedService::DEFAULT_HEIGHT, $service->sanitizeAttributes(['height' => '0'])['height']);
    $this->assertSame(NewsletterEmbedService::DEFAULT_HEIGHT, $service->sanitizeAttributes(['height' => '-1'])['height']);
    $this->assertSame(NewsletterEmbedService::MIN_HEIGHT, $service->sanitizeAttributes(['height' => '199'])['height']);
    $this->assertSame(600, $service->sanitizeAttributes(['height' => '600'])['height']);
    $this->assertSame(NewsletterEmbedService::MAX_HEIGHT, $service->sanitizeAttributes(['height' => '3001'])['height']);

    $this->assertFalse($service->sanitizeAttributes(['showFallbackLink' => false])['showFallbackLink']);
    $this->assertFalse($service->sanitizeAttributes(['showFallbackLink' => '0'])['showFallbackLink']);
    $this->assertFalse($service->sanitizeAttributes(['showFallbackLink' => 'false'])['showFallbackLink']);
    $this->assertTrue($service->sanitizeAttributes(['showFallbackLink' => '1'])['showFallbackLink']);

    $this->assertSame('wide', $service->sanitizeAttributes(['align' => 'wide'])['align']);
    $this->assertSame('full', $service->sanitizeAttributes(['align' => 'full'])['align']);
    $this->assertSame('', $service->sanitizeAttributes(['align' => 'left'])['align']);
  }

  private function createService(): NewsletterEmbedService {
    return new NewsletterEmbedService(
      Stub::makeEmpty(NewslettersRepository::class),
      Stub::makeEmpty(SendingQueuesRepository::class),
      Stub::makeEmpty(NewsletterUrl::class),
      Stub::makeEmpty(WPFunctions::class)
    );
  }
}
