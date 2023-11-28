<?php declare(strict_types = 1);

namespace MailPoet\Test\Doctrine;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SettingEntity;

class RepositoryTest extends \MailPoetTest {
  public function testItCanPersistAndFlush(): void {
    $repository = $this->createRepository();

    $setting = new SettingEntity();
    $setting->setName('name');
    $setting->setValue('value');
    $repository->persist($setting);
    $repository->flush();

    $this->assertSame($this->getEntityFromIdentityMap($setting->getId()), $setting);
  }

  public function testItCanDetachAll(): void {
    $repository = $this->createRepository();

    $setting1 = $this->createSetting('name-1', 'value-1');
    $setting2 = $this->createSetting('name-2', 'value-2');
    $setting3 = $this->createSetting('name-3', 'value-3');

    $this->assertSame($this->getEntityFromIdentityMap($setting1->getId()), $setting1);
    $this->assertSame($this->getEntityFromIdentityMap($setting2->getId()), $setting2);
    $this->assertSame($this->getEntityFromIdentityMap($setting3->getId()), $setting3);

    $repository->detachAll();

    $this->assertNull($this->getEntityFromIdentityMap($setting1->getId()));
    $this->assertNull($this->getEntityFromIdentityMap($setting2->getId()));
    $this->assertNull($this->getEntityFromIdentityMap($setting3->getId()));
  }

  public function testItCanDetachSelectively(): void {
    $repository = $this->createRepository();

    $setting1 = $this->createSetting('name-1', 'value-1');
    $setting2 = $this->createSetting('name-2', 'value-2');
    $setting3 = $this->createSetting('name-3', 'value-3');

    $this->assertSame($this->getEntityFromIdentityMap($setting1->getId()), $setting1);
    $this->assertSame($this->getEntityFromIdentityMap($setting2->getId()), $setting2);
    $this->assertSame($this->getEntityFromIdentityMap($setting3->getId()), $setting3);

    $repository->detachAll(function (SettingEntity $setting) use ($setting1, $setting3) {
      return !in_array($setting->getId(), [$setting1->getId(), $setting3->getId()], true);
    });

    $this->assertSame($this->getEntityFromIdentityMap($setting1->getId()), $setting1);
    $this->assertNull($this->getEntityFromIdentityMap($setting2->getId()));
    $this->assertSame($this->getEntityFromIdentityMap($setting3->getId()), $setting3);
  }

  private function createSetting(string $name, string $value): SettingEntity {
    $setting = new SettingEntity();
    $setting->setName($name);
    $setting->setValue($value);
    $this->entityManager->persist($setting);
    $this->entityManager->flush();
    return $setting;
  }

  /** @return Repository<SettingEntity> */
  private function createRepository(): Repository {
    /** @var Repository<SettingEntity> $repository */
    $repository = new class($this->entityManager) extends Repository {
      protected function getEntityClassName(): string {
        return SettingEntity::class;
      }
    };
    return $repository;
  }

  private function getEntityFromIdentityMap(?int $id): ?SettingEntity {
    /** @var SettingEntity|false $entity */
    $entity = $this->entityManager->getUnitOfWork()->tryGetById($id, SettingEntity::class);
    return $entity ?: null;
  }
}
