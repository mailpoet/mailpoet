<?php

namespace MailPoet\Test\Doctrine\EventListeners;

use MailPoet\Doctrine\Annotations\AnnotationReaderProvider;
use MailPoet\Doctrine\ConfigurationFactory;
use MailPoet\Doctrine\EntityManagerFactory;
use MailPoet\Doctrine\EventListeners\TimestampListener;
use MailPoet\Doctrine\EventListeners\ValidationListener;
use MailPoet\Doctrine\ValidationException;
use MailPoet\Doctrine\Validator\ValidatorFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\Common\Cache\ArrayCache;

require_once __DIR__ . '/ValidatedEntity.php';

class ValidationTest extends \MailPoetTest {
  /** @var WPFunctions */
  private $wp;

  /** @var string */
  private $table_name;

  function _before() {
    $this->wp = new WPFunctions();
    $this->entity_manager = $this->createEntityManager();
    $this->table_name = $this->entity_manager->getClassMetadata(ValidatedEntity::class)->getTableName();
    $this->connection->executeUpdate("DROP TABLE IF EXISTS $this->table_name");
    $this->connection->executeUpdate("
      CREATE TABLE $this->table_name (
        id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name varchar(255) NOT NULL
      )
    ");
  }

  function testItValidatesNewEntity() {
    $entity = new ValidatedEntity();
    $this->entity_manager->persist($entity);
    try {
      $this->entity_manager->flush();
      $this->fail('Validation exception was not thrown.');
    } catch (ValidationException $e) {
      $entity_class = get_class($entity);
      expect($e->getMessage())->same("Validation failed for '$entity_class'.\nDetails:\n  [name] This value should not be blank.");
    }
  }

  function testItValidatesUpdatedEntity() {
    $id = 1;
    $name = 'Test name';
    $this->connection->executeUpdate("INSERT INTO $this->table_name (id, name) VALUES (?, ?)", [$id, $name]);

    $entity = $this->entity_manager->find(ValidatedEntity::class, $id);
    $entity->setName('x');
    try {
      $this->entity_manager->flush();
      $this->fail('Validation exception was not thrown.');
    } catch (ValidationException $e) {
      $entity_class = get_class($entity);
      expect($e->getMessage())->same("Validation failed for '$entity_class'.\nDetails:\n  [name] This value is too short. It should have 3 characters or more.");
    }
  }

  private function createEntityManager() {
    $annotation_reader_provider = new AnnotationReaderProvider();
    $configuration_factory = new ConfigurationFactory(false, $annotation_reader_provider);
    $configuration = $configuration_factory->createConfiguration();

    $metadata_driver = $configuration->newDefaultAnnotationDriver([__DIR__], false);
    $configuration->setMetadataDriverImpl($metadata_driver);
    $configuration->setMetadataCacheImpl(new ArrayCache());

    $validator_factory = new ValidatorFactory($annotation_reader_provider);
    $timestamp_listener = new TimestampListener($this->wp);
    $validation_listener = new ValidationListener($validator_factory->createValidator());
    $entity_manager_factory = new EntityManagerFactory($this->connection, $configuration, $timestamp_listener, $validation_listener);
    return $entity_manager_factory->createEntityManager();
  }
}
