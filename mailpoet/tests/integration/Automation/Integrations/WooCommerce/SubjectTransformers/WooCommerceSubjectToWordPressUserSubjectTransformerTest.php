<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\WooCommerce\SubjectTransformers;

use MailPoet\Automation\Engine\Control\SubjectTransformerHandler;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\CustomerSubject;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\OrderSubject;
use MailPoet\Automation\Integrations\WordPress\Subjects\UserSubject;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\User;
use MailPoetVendor\Doctrine\ORM\EntityManager;

/**
 * @group woo
 */
class WooCommerceSubjectToWordPressUserSubjectTransformerTest extends \MailPoetTest {
  /** @var SubjectTransformerHandler */
  private $subjectTransformerHandler;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var EntityManager */
  private $doctrineEntityManager;

  public function _before(): void {
    $this->subjectTransformerHandler = $this->diContainer->get(SubjectTransformerHandler::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->doctrineEntityManager = $this->diContainer->get(EntityManager::class);
  }

  public function testItResolvesRegisteredCustomerToWordPressUserWithoutSubscriber(): void {
    $user = $this->createUser();
    $this->deleteSubscriberForUser($user);
    $this->assertNull($this->subscribersRepository->findOneBy(['wpUserId' => $user->ID]));

    $subjects = $this->subjectTransformerHandler->getAllSubjects([
      new Subject(CustomerSubject::KEY, ['customer_id' => $user->ID]),
    ]);

    $this->assertSame(['user_id' => $user->ID], $this->getSubjectArgs($subjects, UserSubject::KEY));
  }

  public function testItDoesNotResolveGuestCustomerToWordPressUser(): void {
    $subjects = $this->subjectTransformerHandler->getAllSubjects([
      new Subject(CustomerSubject::KEY, ['customer_id' => 0]),
    ]);

    $this->assertNull($this->getSubjectArgs($subjects, UserSubject::KEY));
  }

  public function testItResolvesRegisteredOrderToWordPressUserWithoutSubscriber(): void {
    $user = $this->createUser();
    $this->deleteSubscriberForUser($user);
    $this->assertNull($this->subscribersRepository->findOneBy(['wpUserId' => $user->ID]));

    $order = new \WC_Order();
    $order->set_customer_id($user->ID);
    $order->save();

    $subjects = $this->subjectTransformerHandler->getAllSubjects([
      new Subject(OrderSubject::KEY, ['order_id' => $order->get_id()]),
    ]);

    $this->assertSame(['user_id' => $user->ID], $this->getSubjectArgs($subjects, UserSubject::KEY));
  }

  public function testItDoesNotResolveGuestOrderToWordPressUser(): void {
    $order = new \WC_Order();
    $order->save();

    $subjects = $this->subjectTransformerHandler->getAllSubjects([
      new Subject(OrderSubject::KEY, ['order_id' => $order->get_id()]),
    ]);

    $this->assertNull($this->getSubjectArgs($subjects, UserSubject::KEY));
  }

  /**
   * @param Subject[] $subjects
   * @return array<string, mixed>|null
   */
  private function getSubjectArgs(array $subjects, string $key): ?array {
    foreach ($subjects as $subject) {
      if ($subject->getKey() === $key) {
        return $subject->getArgs();
      }
    }
    return null;
  }

  private function createUser(): \WP_User {
    $id = uniqid('wc-user-', false);
    return (new User())->createUser($id, 'customer', $id . '@example.com');
  }

  private function deleteSubscriberForUser(\WP_User $user): void {
    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $user->ID]);
    if (!$subscriber) {
      return;
    }
    $this->subscribersRepository->remove($subscriber);
    $this->doctrineEntityManager->flush();
    $this->doctrineEntityManager->clear();
  }
}
