<?php

namespace MailPoet\Doctrine;

use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\EntityRepository as DoctrineEntityRepository;
use MailPoetVendor\Doctrine\ORM\Mapping\ClassMetadata;

/**
 * @template T of object
 */
abstract class Repository {
  /** @var EntityManager */
  protected $entityManager;

  /** @var ClassMetadata */
  protected $classMetadata;

  /** @var DoctrineEntityRepository */
  protected $doctrineRepository;

  public function __construct(EntityManager $entityManager) {
    $this->entityManager = $entityManager;
    $this->classMetadata = $entityManager->getClassMetadata($this->getEntityClassName());
    $this->doctrineRepository = new DoctrineEntityRepository($this->entityManager, $this->classMetadata);
  }

  /**
   * @param array $criteria
   * @param array|null $orderBy
   * @param int|null $limit
   * @param int|null $offset
   * @return T[]
   */
  public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null) {
    return $this->doctrineRepository->findBy($criteria, $orderBy, $limit, $offset);
  }

  /**
   * @param array $criteria
   * @param array|null $orderBy
   * @return T|null
   */
  public function findOneBy(array $criteria, array $orderBy = null) {
    return $this->doctrineRepository->findOneBy($criteria, $orderBy);
  }

  /**
   * @param mixed $id
   * @return T|null
   */
  public function findOneById($id) {
    return $this->doctrineRepository->find($id);
  }

  /**
   * @return T[]
   */
  public function findAll() {
    return $this->doctrineRepository->findAll();
  }

  /**
   * @param T $entity
   */
  public function persist($entity) {
    $this->entityManager->persist($entity);
  }

  public function truncate() {
    $cmd = $this->entityManager->getClassMetadata($this->getEntityClassName());
    $tableName = $cmd->getTableName();
    $connection = $this->entityManager->getConnection();
    $connection->transactional(function(Connection $connection) use ($tableName) {
      $connection->query('SET FOREIGN_KEY_CHECKS=0');
      $q = "TRUNCATE $tableName";
      $connection->executeUpdate($q);
      $connection->query('SET FOREIGN_KEY_CHECKS=1');
    });
  }

  /**
   * @param T $entity
   */
  public function remove($entity) {
    $this->entityManager->remove($entity);
  }

  public function flush() {
    $this->entityManager->flush();
  }

  /**
   * @return class-string<T>
   */
  abstract protected function getEntityClassName();
}
