<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\FilterFactory;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoet\Segments\SegmentDependencyValidator;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\Common\Collections\Collection;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\DBAL\Result;

class FilterHandlerTest extends \MailPoetTest {

  /** @var FilterHandler */
  private $filterHandler;

  public function _before(): void {
    $this->cleanWpUsers();
    $this->filterHandler = $this->diContainer->get(FilterHandler::class);
    $this->tester->createWordPressUser('user-role-test1@example.com', 'editor');
    $this->tester->createWordPressUser('user-role-test2@example.com', 'administrator');
    $this->tester->createWordPressUser('user-role-test3@example.com', 'editor');
    (new SubscriberFactory())->withEmail('user-role-test4@example.com')->create();

    // fetch entities
    /** @var SubscribersRepository $subscribersRepository */
    $subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $subscriber1 = $subscribersRepository->findOneBy(['email' => 'user-role-test1@example.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $subscriber1->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber1->setLastSubscribedAt(new Carbon());
    $subscriber2 = $subscribersRepository->findOneBy(['email' => 'user-role-test2@example.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    $subscriber2->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber2->setLastSubscribedAt(new Carbon());
    $subscriber3 = $subscribersRepository->findOneBy(['email' => 'user-role-test3@example.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber3);
    $subscriber3->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber3->setLastSubscribedAt(new Carbon());
    $this->entityManager->flush();
  }

  public function testItAppliesFilter(): void {
    $segment = $this->getSegment(['editor']);
    $statement = $this->filterHandler->apply($this->getQueryBuilder(), $segment)->execute();
    $this->assertInstanceOf(Result::class, $statement);
    $result = $statement->fetchAll();
    verify($result)->arrayCount(2);
    $this->assertIsArray($result[0]);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertIsArray($result[1]);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    verify($subscriber1->getEmail())->equals('user-role-test1@example.com');
    verify($subscriber2->getEmail())->equals('user-role-test3@example.com');
  }

  public function testItAppliesOrConnectOperator(): void {
    $segment = $this->getSegment(
      ['editor', 'administrator'],
      DynamicSegmentFilterData::CONNECT_TYPE_OR
    );
    $filterHandler = $this->getFilterHandlerWithoutMissingDependencies();
    $statement = $filterHandler->apply($this->getQueryBuilder(), $segment)->execute();
    $this->assertInstanceOf(Result::class, $statement);
    $result = $statement->fetchAll();

    verify($this->getEmailsFromResult($result))->equals([
      'user-role-test1@example.com',
      'user-role-test2@example.com',
      'user-role-test3@example.com',
    ]);
  }

  public function testItAppliesNoneConnectOperator(): void {
    $segment = $this->getSegment(
      ['editor', 'administrator'],
      DynamicSegmentFilterData::CONNECT_TYPE_NONE
    );
    $filterHandler = $this->getFilterHandlerWithoutMissingDependencies();
    $statement = $filterHandler->apply($this->getQueryBuilder(), $segment)->execute();
    $this->assertInstanceOf(Result::class, $statement);
    $result = $statement->fetchAll();

    verify($this->getEmailsFromResult($result))->equals([
      'user-role-test4@example.com',
    ]);
  }

  public function testItAppliesNoneConnectOperatorWithSingleFilter(): void {
    $segment = $this->getSegment(
      ['editor'],
      DynamicSegmentFilterData::CONNECT_TYPE_NONE
    );
    $statement = $this->filterHandler->apply($this->getQueryBuilder(), $segment)->execute();
    $this->assertInstanceOf(Result::class, $statement);
    $result = $statement->fetchAll();

    // Editors are excluded; the administrator and the non-WP-user subscriber remain.
    verify($this->getEmailsFromResult($result))->equals([
      'user-role-test2@example.com',
      'user-role-test4@example.com',
    ]);
  }

  public function testItAppliesNoneConnectOperatorReturnsAllWhenNoSubscribersMatch(): void {
    // No test user has the 'subscriber' role, so the inner subquery is empty and
    // the LEFT JOIN keeps every subscriber.
    $segment = $this->getSegment(
      ['subscriber'],
      DynamicSegmentFilterData::CONNECT_TYPE_NONE
    );
    $statement = $this->filterHandler->apply($this->getQueryBuilder(), $segment)->execute();
    $this->assertInstanceOf(Result::class, $statement);
    $result = $statement->fetchAll();

    verify($this->getEmailsFromResult($result))->equals([
      'user-role-test1@example.com',
      'user-role-test2@example.com',
      'user-role-test3@example.com',
      'user-role-test4@example.com',
    ]);
  }

  public function testItReturnsEmptyResultForNoneWhenAdvancedSegmentsAreUnavailable(): void {
    $segment = $this->getSegment(
      ['editor', 'administrator'],
      DynamicSegmentFilterData::CONNECT_TYPE_NONE
    );
    $statement = $this->filterHandler->apply($this->getQueryBuilder(), $segment)->execute();
    $this->assertInstanceOf(Result::class, $statement);
    $result = $statement->fetchAll();

    verify($result)->arrayCount(0);
  }

  public function testItReturnsEmptyResultForNoneWhenSingleFilterHasMissingPlugin(): void {
    // Models the case where the segment-wide premium check passes (e.g. only
    // one filter, or premium is active), but a specific filter's plugin
    // dependency is missing. For NONE we cannot safely include the affected
    // subscribers, so the whole result must be empty.
    $segment = $this->getSegment(
      ['editor', 'administrator'],
      DynamicSegmentFilterData::CONNECT_TYPE_NONE
    );
    $segmentDependencyValidator = new class extends SegmentDependencyValidator {
      public function __construct() {
      }

      public function getMissingPluginsByAllFilters(Collection $dynamicFilters): array {
        return [];
      }

      public function getMissingPluginsByFilter(DynamicSegmentFilterEntity $dynamicSegmentFilter): array {
        return ['SomePlugin'];
      }
    };
    $filterHandler = new FilterHandler(
      $this->entityManager,
      $segmentDependencyValidator,
      $this->diContainer->get(FilterFactory::class)
    );
    $statement = $filterHandler->apply($this->getQueryBuilder(), $segment)->execute();
    $this->assertInstanceOf(Result::class, $statement);
    $result = $statement->fetchAll();

    verify($result)->arrayCount(0);
  }

  /**
   * @param string[] $roles
   */
  private function getSegment(array $roles, string $connect = DynamicSegmentFilterData::CONNECT_TYPE_AND): SegmentEntity {
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    foreach ($roles as $role) {
      $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, UserRole::TYPE, [
        'wordpressRole' => $role,
        'connect' => $connect,
      ]);
      $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $filterData);
      $segment->addDynamicFilter($dynamicSegmentFilter);
      $this->entityManager->persist($dynamicSegmentFilter);
    }
    $this->entityManager->persist($segment);
    $this->entityManager->flush();
    return $segment;
  }

  private function getFilterHandlerWithoutMissingDependencies(): FilterHandler {
    $segmentDependencyValidator = new class extends SegmentDependencyValidator {
      public function __construct() {
      }

      public function getMissingPluginsByAllFilters(Collection $dynamicFilters): array {
        return [];
      }

      public function getMissingPluginsByFilter(DynamicSegmentFilterEntity $dynamicSegmentFilter): array {
        return [];
      }
    };

    return new FilterHandler(
      $this->entityManager,
      $segmentDependencyValidator,
      $this->diContainer->get(FilterFactory::class)
    );
  }

  private function getEmailsFromResult(array $result): array {
    $emails = [];
    foreach ($result as $subscriberData) {
      $subscriber = $this->entityManager->find(SubscriberEntity::class, $subscriberData['id']);
      $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
      $emails[] = $subscriber->getEmail();
    }
    return $emails;
  }

  private function getQueryBuilder(): QueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("$subscribersTable.id")
      ->from($subscribersTable)
      ->orderBy("$subscribersTable.id", 'ASC');
  }

  public function _after(): void {
    parent::_after();
    $this->cleanWpUsers();
  }

  private function cleanWpUsers(): void {
    $emails = [
      'user-role-test1@example.com',
      'user-role-test2@example.com',
      'user-role-test3@example.com',
    ];
    foreach ($emails as $email) {
      $this->tester->deleteWordPressUser($email);
    }
    foreach (array_merge($emails, ['user-role-test4@example.com']) as $email) {
      $subscriber = $this->entityManager
        ->getRepository(SubscriberEntity::class)
        ->findOneBy(['email' => $email]);
      if ($subscriber instanceof SubscriberEntity) {
        $this->entityManager->remove($subscriber);
      }
    }
    $this->entityManager->flush();
  }
}
