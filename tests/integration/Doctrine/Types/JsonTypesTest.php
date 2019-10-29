<?php

namespace MailPoet\Test\Doctrine\EventListeners;

use Exception;
use MailPoet\Doctrine\Annotations\AnnotationReaderProvider;
use MailPoet\Doctrine\ConfigurationFactory;
use MailPoet\Doctrine\EntityManagerFactory;
use MailPoet\Doctrine\EventListeners\TimestampListener;
use MailPoet\Doctrine\EventListeners\ValidationListener;
use MailPoet\Doctrine\Validator\ValidatorFactory;
use MailPoet\Test\Doctrine\Types\JsonEntity;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\Common\Cache\ArrayCache;
use RuntimeException;

require_once __DIR__ . '/JsonEntity.php';

class JsonTypesTest extends \MailPoetTest {
  /** @var WPFunctions */
  private $wp;

  /** @var string */
  private $table_name;

  /** @var array */
  private $test_data = [
    'key' => [
      'a' => 'string',
      'b' => 10,
      'c' => true,
      'd' => null,
    ],
  ];

  function _before() {
    $this->wp = new WPFunctions();
    $this->entity_manager = $this->createEntityManager();
    $this->table_name = $this->entity_manager->getClassMetadata(JsonEntity::class)->getTableName();
    $this->connection->executeUpdate("DROP TABLE IF EXISTS $this->table_name");
    $this->connection->executeUpdate("
      CREATE TABLE $this->table_name (
        id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        json_data longtext NULL,
        json_or_serialized_data longtext NULL
      )
    ");
  }

  function testItSavesJsonData() {
    $entity = new JsonEntity();
    $entity->setJsonData($this->test_data);
    $entity->setJsonOrSerializedData($this->test_data);
    $this->entity_manager->persist($entity);
    $this->entity_manager->flush();

    $saved_data = $this->connection->executeQuery("SELECT * FROM $this->table_name")->fetch();
    expect($saved_data['json_data'])->same(json_encode($this->test_data));
    expect($saved_data['json_or_serialized_data'])->same(json_encode($this->test_data));
  }

  function testItLoadsJsonData() {
    $this->connection->executeUpdate(
      "INSERT INTO $this->table_name (id, json_data, json_or_serialized_data) VALUES (?, ?, ?)",
      [
        1,
        json_encode($this->test_data),
        json_encode($this->test_data),
      ]
    );

    $entity = $this->entity_manager->find(JsonEntity::class, 1);
    expect($entity->getJsonData())->same($this->test_data);
    expect($entity->getJsonOrSerializedData())->same($this->test_data);
  }

  function testItLoadsSerializedData() {
    $this->connection->executeUpdate(
      "INSERT INTO $this->table_name (id, json_or_serialized_data) VALUES (?, ?)",
      [
        1,
        serialize($this->test_data),
      ]
    );

    $entity = $this->entity_manager->find(JsonEntity::class, 1);
    expect($entity->getJsonData())->null();
    expect($entity->getJsonOrSerializedData())->same($this->test_data);
  }

  function testItSavesNullData() {
    $entity = new JsonEntity();
    $entity->setJsonData(null);
    $entity->setJsonOrSerializedData(null);
    $this->entity_manager->persist($entity);
    $this->entity_manager->flush();

    $saved_data = $this->connection->executeQuery("SELECT * FROM $this->table_name")->fetch();
    expect($saved_data['json_data'])->null();
    expect($saved_data['json_or_serialized_data'])->null();
  }

  function testItLoadsNullData() {
    $this->connection->executeUpdate(
      "INSERT INTO $this->table_name (id, json_data, json_or_serialized_data) VALUES (?, ?, ?)",
      [
        1,
        null,
        null,
      ]
    );

    $entity = $this->entity_manager->find(JsonEntity::class, 1);
    expect($entity->getJsonData())->null();
    expect($entity->getJsonOrSerializedData())->null();
  }

  function testItLoadsEmptyStringAsNull() {
    $this->connection->executeUpdate(
      "INSERT INTO $this->table_name (id, json_data, json_or_serialized_data) VALUES (?, ?, ?)",
      [
        1,
        '',
        '',
      ]
    );

    $entity = $this->entity_manager->find(JsonEntity::class, 1);
    expect($entity->getJsonData())->null();
    expect($entity->getJsonOrSerializedData())->null();
  }

  function testItDoesNotSaveInvalidData() {
    $entity = new JsonEntity();
    $entity->setJsonData("\xB1\x31"); // invalid unicode sequence
    $this->entity_manager->persist($entity);

    $exception = null;
    try {
      $this->entity_manager->flush();
    } catch (Exception $e) {
      $exception = $e;
    }
    expect($exception)->isInstanceOf(RuntimeException::class);
  }

  function testItDoesNotLoadInvalidData() {
    $this->connection->executeUpdate(
      "INSERT INTO $this->table_name (id, json_data) VALUES (?, ?)",
      [
        1,
        '{', // invalid JSON
      ]
    );

    $exception = null;
    try {
      $this->entity_manager->find(JsonEntity::class, 1);
    } catch (Exception $e) {
      $exception = $e;
    }
    expect($exception)->isInstanceOf(RuntimeException::class);
  }

  function _after() {
    parent::_after();
    $this->connection->executeUpdate("DROP TABLE IF EXISTS $this->table_name");
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
