<?php declare(strict_types = 1);

namespace integration\Automation\Integrations\MailPoet\Fields;

use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Integrations\MailPoet\Fields\SubscriberStatisticFieldsFactory;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SubscriberPayload;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterLink as NewsletterLinkFactory;
use MailPoet\Test\DataFactories\StatisticsClicks as StatisticsClicksFactory;
use MailPoet\Test\DataFactories\StatisticsNewsletters as StatisticsNewslettersFactory;
use MailPoet\Test\DataFactories\StatisticsOpens as StatisticsOpensFacctory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetTest;
use MailPoetVendor\Carbon\Carbon;

class SubscriberStatisticsFieldsFactoryTest extends MailPoetTest {
  public function testItCreatesSentCountField(): void {
    $fields = $this->getFieldsMap();

    $subscriber = (new SubscriberFactory())->create();
    $newsletter1 = (new NewsletterFactory())->withSendingQueue()->create();
    $newsletter2 = (new NewsletterFactory())->withSendingQueue()->create();
    (new NewsletterFactory())->withSendingQueue()->create();

    (new StatisticsNewslettersFactory($newsletter1, $subscriber))->withSentAt(new Carbon('-1 week'))->create();
    (new StatisticsNewslettersFactory($newsletter2, $subscriber))->withSentAt(new Carbon('-1 day'))->create();

    // check definitions
    $field = $fields['mailpoet:subscriber:email-sent-count'];
    $this->assertSame('Email — sent count', $field->getName());
    $this->assertSame('integer', $field->getType());
    $this->assertSame([], $field->getArgs());

    // check values
    $payload = new SubscriberPayload($subscriber);
    $this->assertSame(2, $field->getValue($payload));
  }

  public function testItCreatesOpenedCountField(): void {
    $fields = $this->getFieldsMap();

    $subscriber = (new SubscriberFactory())->create();
    $newsletter1 = (new NewsletterFactory())->withSendingQueue()->create();
    $newsletter2 = (new NewsletterFactory())->withSendingQueue()->create();
    (new NewsletterFactory())->withSendingQueue()->create();

    (new StatisticsOpensFacctory($newsletter1, $subscriber))->create(); // open
    (new StatisticsOpensFacctory($newsletter1, $subscriber))->withMachineUserAgentType()->create(); // machine open
    (new StatisticsOpensFacctory($newsletter2, $subscriber))->withMachineUserAgentType()->create(); // machine open

    // check definitions
    $field = $fields['mailpoet:subscriber:email-opened-count'];
    $this->assertSame('Email — opened count', $field->getName());
    $this->assertSame('integer', $field->getType());
    $this->assertSame([], $field->getArgs());

    // check values
    $payload = new SubscriberPayload($subscriber);
    $this->assertSame(1, $field->getValue($payload));
  }

  public function testItCreatesMachineOpenedCountField(): void {
    $fields = $this->getFieldsMap();

    $subscriber = (new SubscriberFactory())->create();
    $newsletter1 = (new NewsletterFactory())->withSendingQueue()->create();
    $newsletter2 = (new NewsletterFactory())->withSendingQueue()->create();
    (new NewsletterFactory())->withSendingQueue()->create();

    (new StatisticsOpensFacctory($newsletter1, $subscriber))->create(); // open
    (new StatisticsOpensFacctory($newsletter1, $subscriber))->withMachineUserAgentType()->create(); // machine open
    (new StatisticsOpensFacctory($newsletter2, $subscriber))->withMachineUserAgentType()->create(); // machine open

    // check definitions
    $field = $fields['mailpoet:subscriber:email-machine-opened-count'];
    $this->assertSame('Email — machine opened count', $field->getName());
    $this->assertSame('integer', $field->getType());
    $this->assertSame([], $field->getArgs());

    // check values
    $payload = new SubscriberPayload($subscriber);
    $this->assertSame(2, $field->getValue($payload));
  }

  public function testItCreatesClickedCountField(): void {
    $fields = $this->getFieldsMap();

    $subscriber = (new SubscriberFactory())->create();
    $newsletter1 = (new NewsletterFactory())->withSendingQueue()->create();
    $newsletter2 = (new NewsletterFactory())->withSendingQueue()->create();
    (new NewsletterFactory())->withSendingQueue()->create();
    $link1 = (new NewsletterLinkFactory($newsletter1))->create();
    $link2 = (new NewsletterLinkFactory($newsletter2))->create();

    (new StatisticsClicksFactory($link1, $subscriber))->create(); // click 1
    (new StatisticsClicksFactory($link2, $subscriber))->create(); // click 2

    // check definitions
    $field = $fields['mailpoet:subscriber:email-clicked-count'];
    $this->assertSame('Email — clicked count', $field->getName());
    $this->assertSame('integer', $field->getType());
    $this->assertSame([], $field->getArgs());

    // check values
    $payload = new SubscriberPayload($subscriber);
    $this->assertSame(2, $field->getValue($payload));
  }

  /** @return array<string, Field> */
  private function getFieldsMap(): array {
    $factory = $this->diContainer->get(SubscriberStatisticFieldsFactory::class);
    $fields = [];
    foreach ($factory->getFields() as $field) {
      $fields[$field->getKey()] = $field;
    }
    return $fields;
  }
}
