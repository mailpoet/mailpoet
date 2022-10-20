<?php

namespace MailPoet\Test\Doctrine\EventListeners;

use Exception;
use MailPoet\Config\SubscriberChangesNotifier;
use MailPoet\Doctrine\Annotations\AnnotationReaderProvider;
use MailPoet\Doctrine\ArrayCache;
use MailPoet\Doctrine\ConfigurationFactory;
use MailPoet\Doctrine\EntityManagerFactory;
use MailPoet\Doctrine\EventListeners\EmojiEncodingListener;
use MailPoet\Doctrine\EventListeners\LastSubscribedAtListener;
use MailPoet\Doctrine\EventListeners\SubscriberListener;
use MailPoet\Doctrine\EventListeners\TimestampListener;
use MailPoet\Doctrine\EventListeners\ValidationListener;
use MailPoet\Doctrine\Validator\ValidatorFactory;
use MailPoet\Test\Doctrine\Types\JsonEntity;
use MailPoet\WP\Emoji;
use MailPoet\WP\Functions as WPFunctions;
use RuntimeException;

require_once __DIR__ . '/JsonEntity.php';

class JsonTypesTest extends \MailPoetTest {
  /** @var WPFunctions */
  private $wp;

  /** @var string */
  private $tableName;

  /** @var array */
  private $testData = [
    'key' => [
      'a' => 'string',
      'b' => 10,
      'c' => true,
      'd' => null,
    ],
  ];

  public function _before() {
    $this->wp = new WPFunctions();
    $this->entityManager = $this->createEntityManager();
    $this->tableName = $this->entityManager->getClassMetadata(JsonEntity::class)->getTableName();
    $this->connection->executeStatement("DROP TABLE IF EXISTS $this->tableName");
    $this->connection->executeStatement("
      CREATE TABLE $this->tableName (
        id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        json_data longtext NULL,
        json_or_serialized_data longtext NULL
      )
    ");
  }

  public function testItSavesJsonData() {
    $entity = new JsonEntity();
    $entity->setJsonData($this->testData);
    $entity->setJsonOrSerializedData($this->testData);
    $this->entityManager->persist($entity);
    $this->entityManager->flush();

    $savedData = $this->connection->executeQuery("SELECT * FROM $this->tableName")->fetchAssociative() ?: [];
    expect($savedData['json_data'])->same(json_encode($this->testData));
    expect($savedData['json_or_serialized_data'])->same(json_encode($this->testData));
  }

  public function testItLoadsJsonData() {
    $this->connection->executeStatement(
      "INSERT INTO $this->tableName (id, json_data, json_or_serialized_data) VALUES (?, ?, ?)",
      [
        1,
        json_encode($this->testData),
        json_encode($this->testData),
      ]
    );

    $entity = $this->entityManager->find(JsonEntity::class, 1);
    assert($entity instanceof JsonEntity); // PHPStan
    expect($entity->getJsonData())->same($this->testData);
    expect($entity->getJsonOrSerializedData())->same($this->testData);
  }

  public function testItLoadsSerializedData() {
    $this->connection->executeStatement(
      "INSERT INTO $this->tableName (id, json_or_serialized_data) VALUES (?, ?)",
      [
        1,
        serialize($this->testData),
      ]
    );

    $entity = $this->entityManager->find(JsonEntity::class, 1);
    assert($entity instanceof JsonEntity); // PHPStan
    expect($entity->getJsonData())->null();
    expect($entity->getJsonOrSerializedData())->same($this->testData);
  }

  public function testItSavesNullData() {
    $entity = new JsonEntity();
    $entity->setJsonData(null);
    $entity->setJsonOrSerializedData(null);
    $this->entityManager->persist($entity);
    $this->entityManager->flush();

    $savedData = $this->connection->executeQuery("SELECT * FROM $this->tableName")->fetchAssociative() ?: [];
    expect($savedData)->array();
    expect($savedData['json_data'])->null();
    expect($savedData['json_or_serialized_data'])->null();
  }

  public function testItLoadsNullData() {
    $this->connection->executeStatement(
      "INSERT INTO $this->tableName (id, json_data, json_or_serialized_data) VALUES (?, ?, ?)",
      [
        1,
        null,
        null,
      ]
    );

    $entity = $this->entityManager->find(JsonEntity::class, 1);
    assert($entity instanceof JsonEntity); // PHPStan
    expect($entity->getJsonData())->null();
    expect($entity->getJsonOrSerializedData())->null();
  }

  public function testItLoadsEmptyStringAsNull() {
    $this->connection->executeStatement(
      "INSERT INTO $this->tableName (id, json_data, json_or_serialized_data) VALUES (?, ?, ?)",
      [
        1,
        '',
        '',
      ]
    );

    $entity = $this->entityManager->find(JsonEntity::class, 1);
    assert($entity instanceof JsonEntity); // PHPStan
    expect($entity->getJsonData())->null();
    expect($entity->getJsonOrSerializedData())->null();
  }

  public function testItDoesNotSaveInvalidData() {
    $entity = new JsonEntity();
    $entity->setJsonData(["\xB1\x31"]); // invalid unicode sequence
    $this->entityManager->persist($entity);

    $exception = null;
    try {
      $this->entityManager->flush();
    } catch (Exception $e) {
      $exception = $e;
    }
    expect($exception)->isInstanceOf(RuntimeException::class);
  }

  public function testItDoesNotLoadInvalidData() {
    $this->connection->executeStatement(
      "INSERT INTO $this->tableName (id, json_data) VALUES (?, ?)",
      [
        1,
        '{', // invalid JSON
      ]
    );

    $exception = null;
    try {
      $this->entityManager->find(JsonEntity::class, 1);
    } catch (Exception $e) {
      $exception = $e;
    }
    expect($exception)->isInstanceOf(RuntimeException::class);
  }

  public function _after() {
    parent::_after();
    $this->connection->executeStatement("DROP TABLE IF EXISTS $this->tableName");
  }

  private function createEntityManager() {
    $annotationReaderProvider = new AnnotationReaderProvider();
    $configurationFactory = new ConfigurationFactory($annotationReaderProvider, false);
    $configuration = $configurationFactory->createConfiguration();

    $metadataDriver = $configuration->newDefaultAnnotationDriver([__DIR__], false);
    $configuration->setMetadataDriverImpl($metadataDriver);
    $configuration->setMetadataCacheImpl(new ArrayCache());

    $validatorFactory = new ValidatorFactory($annotationReaderProvider);
    $timestampListener = new TimestampListener($this->wp);
    $validationListener = new ValidationListener($validatorFactory->createValidator());
    $emojiEncodingListener = new EmojiEncodingListener(new Emoji($this->wp));
    $lastSubscribedAtListener = new LastSubscribedAtListener($this->wp);
    $subscriberListener = new SubscriberListener(new SubscriberChangesNotifier($this->wp));
    $entityManagerFactory = new EntityManagerFactory(
      $this->connection,
      $configuration,
      $timestampListener,
      $validationListener,
      $emojiEncodingListener,
      $lastSubscribedAtListener,
      $subscriberListener
    );
    return $entityManagerFactory->createEntityManager();
  }
}
