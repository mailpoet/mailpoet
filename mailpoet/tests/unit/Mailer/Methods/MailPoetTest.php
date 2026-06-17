<?php declare(strict_types = 1);

namespace MailPoet\Test\Mailer\Methods;

use MailPoet\Mailer\Methods\MailPoet;
use MailPoet\Newsletter\Sending\TemplateBatch;
use MailPoet\Util\Url;

class MailPoetTest extends \MailPoetUnitTest {
  public function testGetBodyCanBuildTemplatedBatchPayload(): void {
    $batch = new TemplateBatch([
      'id' => 123,
      'subject' => 'Hello {{mailpoet_mss_1}}',
      'body' => [
        'html' => '<p>{{mailpoet_mss_1}}</p>',
        'text' => '{{mailpoet_mss_1}}',
      ],
    ]);
    $batch->addSubstitutions(['{{mailpoet_mss_1}}' => 'Rosta &amp; Co']);
    $batch->addSubstitutions(['{{mailpoet_mss_1}}' => 'Jane &amp; Co']);

    $body = $this->createMethod()->getBody(
      $batch,
      ['Rosta <rosta@example.com>', 'jane@example.com'],
      [
        'unsubscribe_url' => [
          'https://example.com/unsubscribe/1',
          'http://example.com/unsubscribe/2',
        ],
        'one_click_unsubscribe' => [
          'https://example.com/one-click/1',
          'https://example.com/one-click/2',
        ],
        'meta' => [
          ['campaign_id' => 'campaign-1'],
          ['campaign_id' => 'campaign-1'],
        ],
      ]
    );

    $this->assertSame([
      'format' => 'template_batch_v1',
      'from' => [
        'address' => 'sender@example.com',
        'name' => 'Sender',
      ],
      'reply_to' => [
        'address' => 'reply@example.com',
        'name' => 'Reply',
      ],
      'template' => [
        'subject' => 'Hello {{mailpoet_mss_1}}',
        'html' => '<p>{{mailpoet_mss_1}}</p>',
        'text' => '{{mailpoet_mss_1}}',
      ],
      'messages' => [
        [
          'to' => [
            'address' => 'rosta@example.com',
            'name' => 'Rosta',
          ],
          'substitutions' => ['{{mailpoet_mss_1}}' => 'Rosta &amp; Co'],
          'unsubscribe' => [
            'url' => 'https://example.com/one-click/1',
            'post' => true,
          ],
          'meta' => ['campaign_id' => 'campaign-1'],
        ],
        [
          'to' => [
            'address' => 'jane@example.com',
            'name' => '',
          ],
          'substitutions' => ['{{mailpoet_mss_1}}' => 'Jane &amp; Co'],
          'unsubscribe' => [
            'url' => 'http://example.com/unsubscribe/2',
            'post' => false,
          ],
          'meta' => ['campaign_id' => 'campaign-1'],
        ],
      ],
    ], $body);
  }

  private function createMethod(): MailPoet {
    $url = $this->createMock(Url::class);
    $url->method('isUsingHttps')->willReturnCallback(function(string $url): bool {
      return strpos($url, 'https://') === 0;
    });

    $reflection = new \ReflectionClass(MailPoet::class);
    /** @var MailPoet $method */
    $method = $reflection->newInstanceWithoutConstructor();
    $method->sender = [
      'from_email' => 'sender@example.com',
      'from_name' => 'Sender',
    ];
    $method->replyTo = [
      'reply_to_email' => 'reply@example.com',
      'reply_to_name' => 'Reply',
    ];

    $urlProperty = $reflection->getProperty('url');
    $urlProperty->setAccessible(true);
    $urlProperty->setValue($method, $url);

    return $method;
  }
}
