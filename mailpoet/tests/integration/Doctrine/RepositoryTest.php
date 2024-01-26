<?php declare(strict_types = 1);

namespace MailPoet\Test\Doctrine;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SettingEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\CustomField;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetVendor\Doctrine\Common\Collections\Criteria;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class RepositoryTest extends \MailPoetTest {
  public function testItCanPersistAndFlush(): void {
    $repository = $this->createRepository(SettingEntity::class);

    $setting = new SettingEntity();
    $setting->setName('name');
    $setting->setValue('value');
    $repository->persist($setting);
    $repository->flush();

    $this->assertSame($this->getEntityFromIdentityMap(SettingEntity::class, $setting->getId()), $setting);
  }

  public function testItCanDetachAll(): void {
    $repository = $this->createRepository(SettingEntity::class);

    $setting1 = $this->createSetting('name-1', 'value-1');
    $setting2 = $this->createSetting('name-2', 'value-2');
    $setting3 = $this->createSetting('name-3', 'value-3');

    $this->assertSame($this->getEntityFromIdentityMap(SettingEntity::class, $setting1->getId()), $setting1);
    $this->assertSame($this->getEntityFromIdentityMap(SettingEntity::class, $setting2->getId()), $setting2);
    $this->assertSame($this->getEntityFromIdentityMap(SettingEntity::class, $setting3->getId()), $setting3);

    $repository->detachAll();

    $this->assertNull($this->getEntityFromIdentityMap(SettingEntity::class, $setting1->getId()));
    $this->assertNull($this->getEntityFromIdentityMap(SettingEntity::class, $setting2->getId()));
    $this->assertNull($this->getEntityFromIdentityMap(SettingEntity::class, $setting3->getId()));
  }

  public function testItCanDetachSelectively(): void {
    $repository = $this->createRepository(SettingEntity::class);

    $setting1 = $this->createSetting('name-1', 'value-1');
    $setting2 = $this->createSetting('name-2', 'value-2');
    $setting3 = $this->createSetting('name-3', 'value-3');

    $this->assertSame($this->getEntityFromIdentityMap(SettingEntity::class, $setting1->getId()), $setting1);
    $this->assertSame($this->getEntityFromIdentityMap(SettingEntity::class, $setting2->getId()), $setting2);
    $this->assertSame($this->getEntityFromIdentityMap(SettingEntity::class, $setting3->getId()), $setting3);

    $repository->detachAll(
      new Criteria(Criteria::expr()->notIn('id', [$setting1->getId(), $setting3->getId()]))
    );

    $this->assertSame($this->getEntityFromIdentityMap(SettingEntity::class, $setting1->getId()), $setting1);
    $this->assertNull($this->getEntityFromIdentityMap(SettingEntity::class, $setting2->getId()));
    $this->assertSame($this->getEntityFromIdentityMap(SettingEntity::class, $setting3->getId()), $setting3);
  }

  public function testItCanDetachByRelation(): void {
    $repository = $this->createRepository(SubscriberCustomFieldEntity::class);

    $subscriber = (new Subscriber())->create();
    $cf1 = (new CustomField())->withName('cf-1')->create();
    $cf2 = (new CustomField())->withName('cf-2')->create();
    $cf3 = (new CustomField())->withName('cf-3')->create();


    $cfs1 = $this->createSubscriberCustomField($subscriber, $cf1, 'value-1');
    $cfs2 = $this->createSubscriberCustomField($subscriber, $cf2, 'value-2');
    $cfs3 = $this->createSubscriberCustomField($subscriber, $cf3, 'value-3');

    $this->assertSame($this->getEntityFromIdentityMap(SubscriberCustomFieldEntity::class, $cfs1->getId()), $cfs1);
    $this->assertSame($this->getEntityFromIdentityMap(SubscriberCustomFieldEntity::class, $cfs2->getId()), $cfs2);
    $this->assertSame($this->getEntityFromIdentityMap(SubscriberCustomFieldEntity::class, $cfs3->getId()), $cfs3);

    // test detaching cfs-3 by cf-3 reference
    $this->entityManager->detach($cf3);
    $this->entityManager->detach($cfs3);
    $cf3Ref = $this->entityManager->getReference(CustomFieldEntity::class, $cf3->getId());
    $cfs3 = $this->entityManager->find(SubscriberCustomFieldEntity::class, $cfs3->getId());

    $this->assertInstanceOf(SubscriberCustomFieldEntity::class, $cfs3);
    $this->assertSame($this->getEntityFromIdentityMap(SubscriberCustomFieldEntity::class, $cfs3->getId()), $cfs3);

    $repository->detachAll(
      new Criteria(Criteria::expr()->notIn('customField', [$cf1, $cf3Ref]))
    );

    $this->assertSame($this->getEntityFromIdentityMap(SubscriberCustomFieldEntity::class, $cfs1->getId()), $cfs1);
    $this->assertNull($this->getEntityFromIdentityMap(SubscriberCustomFieldEntity::class, $cfs2->getId()));
    $this->assertSame($this->getEntityFromIdentityMap(SubscriberCustomFieldEntity::class, $cfs3->getId()), $cfs3);
  }

  public function testItCanRefreshAll(): void {
    $repository = $this->createRepository(SettingEntity::class);

    $setting1 = $this->createSetting('name-1', 'value-1');
    $setting2 = $this->createSetting('name-2', 'value-2');
    $setting3 = $this->createSetting('name-3', 'value-3');

    $this->entityManager->createQueryBuilder()
      ->update(SettingEntity::class, 's')
      ->set('s.value', ':value')
      ->where('s.name = :name')
      ->setParameter('value', 'new-value-1')
      ->setParameter('name', 'name-1')
      ->getQuery()
      ->execute();

    $this->entityManager->createQueryBuilder()
      ->update(SettingEntity::class, 's')
      ->set('s.value', ':value')
      ->where('s.name = :name')
      ->setParameter('value', 'new-value-2')
      ->setParameter('name', 'name-2')
      ->getQuery()
      ->execute();

    $this->assertSame($setting1->getValue(), 'value-1');
    $this->assertSame($setting2->getValue(), 'value-2');
    $this->assertSame($setting3->getValue(), 'value-3');

    $repository->refreshAll();

    $this->assertSame($setting1->getValue(), 'new-value-1');
    $this->assertSame($setting2->getValue(), 'new-value-2');
    $this->assertSame($setting3->getValue(), 'value-3');
  }

  public function testItCanRefreshSelectively(): void {
    $repository = $this->createRepository(SettingEntity::class);

    $setting1 = $this->createSetting('name-1', 'value-1');
    $setting2 = $this->createSetting('name-2', 'value-2');
    $setting3 = $this->createSetting('name-3', 'value-3');

    $this->entityManager->createQueryBuilder()
      ->update(SettingEntity::class, 's')
      ->set('s.value', ':value')
      ->where('s.name = :name')
      ->setParameter('value', 'new-value-1')
      ->setParameter('name', 'name-1')
      ->getQuery()
      ->execute();

    $this->entityManager->createQueryBuilder()
      ->update(SettingEntity::class, 's')
      ->set('s.value', ':value')
      ->where('s.name = :name')
      ->setParameter('value', 'new-value-2')
      ->setParameter('name', 'name-2')
      ->getQuery()
      ->execute();

    $this->assertSame($setting1->getValue(), 'value-1');
    $this->assertSame($setting2->getValue(), 'value-2');
    $this->assertSame($setting3->getValue(), 'value-3');

    $repository->refreshAll(
      new Criteria(Criteria::expr()->in('name', ['name-1', 'name-3']))
    );

    $this->assertSame($setting1->getValue(), 'new-value-1');
    $this->assertSame($setting2->getValue(), 'value-2');
    $this->assertSame($setting3->getValue(), 'value-3');
  }

  private function createSetting(string $name, string $value): SettingEntity {
    $setting = new SettingEntity();
    $setting->setName($name);
    $setting->setValue($value);
    $this->entityManager->persist($setting);
    $this->entityManager->flush();
    return $setting;
  }

  private function createSubscriberCustomField(SubscriberEntity $subscriber, CustomFieldEntity $customField, string $value): SubscriberCustomFieldEntity {
    $subscriberCustomField = new SubscriberCustomFieldEntity($subscriber, $customField, $value);
    $this->entityManager->persist($subscriberCustomField);
    $this->entityManager->flush();
    return $subscriberCustomField;
  }

  /**
   * @template T of object
   * @param class-string<T> $entityClass
   * @return Repository<T>
   */
  private function createRepository(string $entityClass): Repository {
    /** @var Repository<T> $repository */
    $repository = new class($this->entityManager, $entityClass) extends Repository {
      private string $entityClass;

      public function __construct(
        EntityManager $entityManager,
        string $entityClass
      ) {
        $this->entityClass = $entityClass;
        parent::__construct($entityManager);
      }

      protected function getEntityClassName(): string {
        /** @var class-string<T> $entityClass */
        $entityClass = $this->entityClass;
        return $entityClass;
      }
    };
    return $repository;
  }

  /**
   * @template T
   * @param class-string<T> $entityClass
   * @return T|null
   */
  private function getEntityFromIdentityMap(string $entityClass, ?int $id) {
    /** @var T|false $entity */
    $entity = $this->entityManager->getUnitOfWork()->tryGetById($id, $entityClass);
    return $entity ?: null;
  }
}
