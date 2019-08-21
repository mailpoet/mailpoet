<?php

namespace MailPoet\Features;

use MailPoet\Entities\FeatureFlagEntity;
use MailPoet\Doctrine\Repository;

/**
 * @method FeatureFlagEntity[] findAll()
 * @method FeatureFlagEntity|null findOneBy(array $criteria, array $order_by = null)
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
    $feature_flag = $this->findOneBy([
      'name' => $data['name'],
    ]);
    if (!$feature_flag) {
      $feature_flag = new FeatureFlagEntity($data['name']);
      $this->persist($feature_flag);
    }
    if (array_key_exists('value', $data)) {
      $feature_flag->setValue($data['value']);
    }

    try {
      $this->flush();
    } catch (\Exception $e) {
      throw new \RuntimeException("Error when saving feature " . $data['name']);
    }
    return $feature_flag;
  }
}
