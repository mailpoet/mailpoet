<?php

namespace MailPoet\Test\Doctrine\EventListeners;

use MailPoet\Doctrine\Annotations\AnnotationReaderProvider;
use MailPoet\Doctrine\ConfigurationFactory;
use MailPoet\Doctrine\EntityManagerFactory;
use MailPoet\Doctrine\EventListeners\EmojiEncodingListener;
use MailPoet\Doctrine\EventListeners\LastSubscribedAtListener;
use MailPoet\Doctrine\EventListeners\TimestampListener;
use MailPoet\Doctrine\EventListeners\ValidationListener;
use MailPoet\Doctrine\Validator\ValidationException;
use MailPoet\Doctrine\Validator\ValidatorFactory;
use MailPoet\WP\Emoji;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\Common\Cache\ArrayCache;

require_once __DIR__ . '/ValidatedEntity.php';

class ValidationTest extends \MailPoetTest {
  /** @var WPFunctions */
  private $wp;

  /** @var string */
  private $tableName;

  public function _before() {
    $this->wp = new WPFunctions();
    $this->entityManager = $this->createEntityManager();
    $this->tableName = $this->entityManager->getClassMetadata(ValidatedEntity::class)->getTableName();
    $this->connection->executeUpdate("DROP TABLE IF EXISTS $this->tableName");
    $this->connection->executeUpdate("
      CREATE TABLE $this->tableName (
        id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name varchar(255) NOT NULL
      )
    ");
  }

  public function testItValidatesNewEntity() {
    $entity = new ValidatedEntity();
    $this->entityManager->persist($entity);
    try {
      $this->entityManager->flush();
      $this->fail('Validation exception was not thrown.');
    } catch (ValidationException $e) {
      $entityClass = get_class($entity);
      expect($e->getMessage())->same("Validation failed for '$entityClass'.\nDetails:\n  [name] This value should not be blank.");
    }
  }

  public function testItValidatesUpdatedEntity() {
    $id = 1;
    $name = 'Test name';
    $this->connection->executeUpdate("INSERT INTO $this->tableName (id, name) VALUES (?, ?)", [$id, $name]);

    /** @var ValidatedEntity $entity */
    $entity = $this->entityManager->find(ValidatedEntity::class, $id);
    $entity->setName('x');
    try {
      $this->entityManager->flush();
      $this->fail('Validation exception was not thrown.');
    } catch (ValidationException $e) {
      $entityClass = get_class($entity);
      expect($e->getMessage())->same("Validation failed for '$entityClass'.\nDetails:\n  [name] This value is too short. It should have 3 characters or more.");
    }
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
    $entityManagerFactory = new EntityManagerFactory(
      $this->connection,
      $configuration,
      $timestampListener,
      $validationListener,
      $emojiEncodingListener,
      $lastSubscribedAtListener
    );
    return $entityManagerFactory->createEntityManager();
  }
}
