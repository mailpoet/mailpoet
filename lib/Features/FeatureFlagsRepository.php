<?php

namespace MailPoet\Features;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\FeatureFlagEntity;

/**
 * @method FeatureFlagEntity[] findAll()
 * @method FeatureFlagEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method void persist(FeatureFlagEntity $entity)
 * @method void remove(FeatureFlagEntity $entity)
 */
class FeatureFlagsRepository extends Repository {
  protected function getEntityClassName() {
    return FeatureFlagEntity::class;
  }

  /**
   * @param array $data
   * @throws \RuntimeException
   * @throws \InvalidArgumentException
   * @return FeatureFlagEntity
   */
  public function createOrUpdate(array $data = []) {
    if (!$data['name']) {
      throw new \InvalidArgumentException('Missing name');
    }
    $featureFlag = $this->findOneBy([
      'name' => $data['name'],
    ]);
    if (!$featureFlag) {
      $featureFlag = new FeatureFlagEntity($data['name']);
      $this->persist($featureFlag);
    }

    if (array_key_exists('value', $data)) {
      $featureFlag->setValue($data['value']);
    }

    try {
      $this->flush();
    } catch (\Exception $e) {
      throw new \RuntimeException("Error when saving feature " . $data['name']);
    }
    return $featureFlag;
  }
}
