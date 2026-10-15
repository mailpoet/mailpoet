<?php declare(strict_types = 1);

namespace MailPoet\Test\Mailer\Methods;

use MailPoet\InvalidStateException;
use MailPoet\Mailer\Methods\MailPoet;
use MailPoet\Newsletter\Sending\TemplateBatch;
use MailPoet\Util\Url;

class MailPoetTest extends \MailPoetUnitTest {
  public function testGetBodyCanBuildTemplatedBatchPayload(): void {
    $batch = new TemplateBatch([
      'id' => 123,
      'subject' => 'Hello {{mp_mss_1}}',
      'body' => [
        'html' => '<p>{{mp_mss_1}}</p>',
        'text' => '{{mp_mss_1}}',
      ],
    ]);
    $batch->addSubstitutions([
      'subject' => ['{{mp_mss_1}}' => 'Rosta & Co'],
      'html' => ['{{mp_mss_1}}' => 'Rosta &amp; Co'],
      'text' => ['{{mp_mss_1}}' => 'Rosta & Co'],
    ]);
    $batch->addSubstitutions([
      'subject' => ['{{mp_mss_1}}' => 'Jane & Co'],
      'html' => ['{{mp_mss_1}}' => 'Jane &amp; Co'],
      'text' => ['{{mp_mss_1}}' => 'Jane & Co'],
    ]);

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
        'subject' => 'Hello {{mp_mss_1}}',
        'html' => '<p>{{mp_mss_1}}</p>',
        'text' => '{{mp_mss_1}}',
      ],
      'messages' => [
        [
          'to' => [
            'address' => 'rosta@example.com',
            'name' => 'Rosta',
          ],
          'substitutions' => [
            'subject' => ['{{mp_mss_1}}' => 'Rosta & Co'],
            'html' => ['{{mp_mss_1}}' => 'Rosta &amp; Co'],
            'text' => ['{{mp_mss_1}}' => 'Rosta & Co'],
          ],
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
          'substitutions' => [
            'subject' => ['{{mp_mss_1}}' => 'Jane & Co'],
            'html' => ['{{mp_mss_1}}' => 'Jane &amp; Co'],
            'text' => ['{{mp_mss_1}}' => 'Jane & Co'],
          ],
          'unsubscribe' => [
            'url' => 'http://example.com/unsubscribe/2',
            'post' => false,
          ],
          'meta' => ['campaign_id' => 'campaign-1'],
        ],
      ],
    ], $body);
  }

  public function testGetBodyRejectsTemplatedBatchWhenSubstitutionsDoNotMatchRecipients(): void {
    $batch = new TemplateBatch(['id' => 1, 'subject' => 'Hi {{mp_mss_1}}', 'body' => ['html' => '', 'text' => '']]);
    $batch->addSubstitutions(['subject' => ['{{mp_mss_1}}' => 'Rosta'], 'html' => [], 'text' => []]);

    $this->expectException(InvalidStateException::class);
    $this->createMethod()->getBody($batch, ['rosta@example.com', 'jane@example.com']);
  }

  public function testGetBodyOmitsEmptyTemplatePartsAndReplyToName(): void {
    $batch = new TemplateBatch(['id' => 1, 'subject' => 'Hi', 'body' => ['html' => '<p>Hi</p>', 'text' => '']]);
    $batch->addSubstitutions(['subject' => [], 'html' => [], 'text' => []]);
    $method = $this->createMethod();
    $method->replyTo = ['reply_to_email' => 'reply@example.com', 'reply_to_name' => ''];

    $body = $method->getBody($batch, ['rosta@example.com']);

    $this->assertSame(['subject' => 'Hi', 'html' => '<p>Hi</p>'], $body['template']);
    $this->assertSame(['address' => 'reply@example.com'], $body['reply_to']);
    $this->assertArrayNotHasKey('unsubscribe', $body['messages'][0]);
    $this->assertArrayNotHasKey('meta', $body['messages'][0]);
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
