<?php declare(strict_types = 1);

namespace MailPoet\Test\Settings;

use MailPoet\Entities\SettingEntity;
use MailPoet\Settings\SettingsRepository;
use MailPoetTest;

class SettingsRepositoryTest extends MailPoetTest {
  public function testItFetchesAlwaysFreshData(): void {
    $repository = $this->diContainer->get(SettingsRepository::class);

    $setting = new SettingEntity();
    $setting->setName('name');
    $setting->setValue('value');
    $this->entityManager->persist($setting);
    $this->entityManager->flush();

    $this->assertSame('value', $setting->getValue());
    $this->entityManager->createQueryBuilder()
      ->update(SettingEntity::class, 's')
      ->set('s.value', ':value')
      ->where('s.name = :name')
      ->setParameter('value', 'new value')
      ->setParameter('name', 'name')
      ->getQuery()
      ->execute();
    $this->assertSame('value', $setting->getValue());

    $newSetting = $repository->findOneByName('name');
    $this->assertSame($setting, $newSetting);
    $this->assertSame('new value', $setting->getValue());
    $this->assertSame('new value', $newSetting->getValue());
  }

  public function testUpdateRefreshesExistingData(): void {
    $repository = $this->diContainer->get(SettingsRepository::class);

    $setting = new SettingEntity();
    $setting->setName('name');
    $setting->setValue('value');
    $this->entityManager->persist($setting);
    $this->entityManager->flush();

    $this->assertSame('value', $setting->getValue());
    $repository->createOrUpdateByName('name', 'new value');
    $this->assertSame('new value', $setting->getValue());
  }
}
