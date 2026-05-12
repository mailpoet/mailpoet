<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\SubjectTransformers;

use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Automation\Integrations\MailPoet\SubjectTransformers\CustomerSubjectToSubscriberSubjectTransformer;
use MailPoet\Automation\Integrations\MailPoet\SubjectTransformers\SubscriberSubjectToWordPressUserSubjectTransformer;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\CustomerSubject;
use MailPoet\Automation\Integrations\WordPress\Subjects\UserSubject;
use MailPoet\Test\DataFactories\Subscriber;

/**
 * @group woo
 */
class SubscriberSubjectTransformersTest extends \MailPoetTest {
  public function testCustomerSubjectTransformerReturnsNullWhenSubscriberDoesNotExist(): void {
    $transformer = $this->diContainer->get(CustomerSubjectToSubscriberSubjectTransformer::class);

    $this->assertNull($transformer->transform(new Subject(CustomerSubject::KEY, ['customer_id' => 999999])));
  }

  public function testSubscriberSubjectTransformerReturnsNullWhenSubscriberDoesNotExist(): void {
    $transformer = $this->diContainer->get(SubscriberSubjectToWordPressUserSubjectTransformer::class);

    $this->assertNull($transformer->transform(new Subject(SubscriberSubject::KEY, ['subscriber_id' => 999999])));
  }

  public function testSubscriberSubjectTransformerReturnsNullWhenSubscriberHasNoWordPressUser(): void {
    $subscriber = (new Subscriber())->create();
    $transformer = $this->diContainer->get(SubscriberSubjectToWordPressUserSubjectTransformer::class);

    $this->assertNull($transformer->transform(new Subject(SubscriberSubject::KEY, ['subscriber_id' => $subscriber->getId()])));
  }

  public function testSubscriberSubjectTransformerReturnsWordPressUserSubject(): void {
    $subscriber = (new Subscriber())->withWpUserId(123)->create();
    $transformer = $this->diContainer->get(SubscriberSubjectToWordPressUserSubjectTransformer::class);

    $subject = $transformer->transform(new Subject(SubscriberSubject::KEY, ['subscriber_id' => $subscriber->getId()]));

    $this->assertInstanceOf(Subject::class, $subject);
    $this->assertSame(UserSubject::KEY, $subject->getKey());
    $this->assertSame(['user_id' => 123], $subject->getArgs());
  }
}
