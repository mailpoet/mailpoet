<?php declare(strict_types = 1);

namespace integration\Automation\Integrations\MailPoet\Fields;

use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Integrations\MailPoet\Payloads\NewsletterLinkPayload;
use MailPoet\Automation\Integrations\MailPoet\Subjects\NewsletterLinkSubject;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\SendingQueueEntity;

class NewsletterLinkFieldsTest extends \MailPoetTest {
  public function testUrlField(): void {

    $fields = $this->getFieldsMap();
    $field = $fields['mailpoet:email_link:url'];
    $this->assertInstanceOf(Field::class, $field);
    $this->assertEquals('mailpoet:email_link:url', $field->getKey());
    $this->assertEquals('Link URL', $field->getName());
    $this->assertEquals('string', $field->getType());

    $expectedUrl = 'https://someurl.com';
    $payload = $this->getPayload(1, $expectedUrl);
    $this->assertEquals($expectedUrl, $field->getValue($payload));
  }

  public function testCreatedField(): void {
    $fields = $this->getFieldsMap();
    $field = $fields['mailpoet:email_link:created'];
    $this->assertInstanceOf(Field::class, $field);
    $this->assertEquals('mailpoet:email_link:created', $field->getKey());
    $this->assertEquals('Created', $field->getName());
    $this->assertEquals('datetime', $field->getType());

    $expectedDate = new \DateTimeImmutable('2022-01-01 00:00:00');
    $payload = $this->getPayload(1, 'url', $expectedDate);
    $this->assertEquals($expectedDate, $field->getValue($payload));
  }

  public function testIdField(): void {
    $fields = $this->getFieldsMap();
    $field = $fields['mailpoet:email_link:id'];
    $this->assertInstanceOf(Field::class, $field);
    $this->assertEquals('mailpoet:email_link:id', $field->getKey());
    $this->assertEquals('Link ID', $field->getName());
    $this->assertEquals('integer', $field->getType());

    $expectedId = 5;
    $payload = $this->getPayload($expectedId);
    $this->assertEquals($expectedId, $field->getValue($payload));
  }

  private function getPayload(int $id = 1, string $url = 'https://example.com', \DateTimeImmutable $created = null): NewsletterLinkPayload {
    $newsletter = new NewsletterEntity();
    $queue = new SendingQueueEntity();
    $newsletterLink = new NewsletterLinkEntity($newsletter, $queue, $url, 'hash');
    $newsletterLink->setCreatedAt($created ?? new \DateTimeImmutable());
    $newsletterLink->setId($id);
    return new NewsletterLinkPayload($newsletterLink);
  }

  /**
   * @return array<string, Field>
   */
  private function getFieldsMap(): array {
    $factory = $this->diContainer->get(NewsletterLinkSubject::class);
    $fields = [];
    foreach ($factory->getFields() as $field) {
      $fields[$field->getKey()] = $field;
    }
    return $fields;
  }
}
