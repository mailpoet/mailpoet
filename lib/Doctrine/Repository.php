<?php

namespace MailPoet\Doctrine;

use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\EntityRepository as DoctrineEntityRepository;
use MailPoetVendor\Doctrine\ORM\Mapping\ClassMetadata;

abstract class Repository {
  /** @var EntityManager */
  protected $entity_manager;

  /** @var ClassMetadata */
  protected $class_metadata;

  /** @var DoctrineEntityRepository */
  protected $doctrine_repository;

  function __construct(EntityManager $entity_manager) {
    $this->entity_manager = $entity_manager;
    $this->class_metadata = $entity_manager->getClassMetadata($this->getEntityClassName());
    $this->doctrine_repository = new DoctrineEntityRepository($this->entity_manager, $this->class_metadata);
  }

  /**
   * @param array $criteria
   * @param array|null $order_by
   * @param int|null $limit
   * @param int|null $offset
   * @return array
   */
  function findBy(array $criteria, array $order_by = null, $limit = null, $offset = null) {
    return $this->doctrine_repository->findBy($criteria, $order_by, $limit, $offset);
  }

  /**
   * @param array $criteria
   * @param array|null $order_by
   * @return object|null
   */
  function findOneBy(array $criteria, array $order_by = null) {
    return $this->doctrine_repository->findOneBy($criteria, $order_by);
  }

  /**
   * @param mixed $id
   * @return object|null
   */
  function findOneById($id) {
    return $this->doctrine_repository->find($id);
  }

  /**
   * @param object $entity
   */
  function persist($entity) {
    $this->entity_manager->persist($entity);
  }

  /**
   * @param object $entity
   */
  function remove($entity) {
    $this->entity_manager->remove($entity);
  }

  function flush() {
    $this->entity_manager->flush();
  }

  /**
   * @return string
   */
  abstract protected function getEntityClassName();
}
