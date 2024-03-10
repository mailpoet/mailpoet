<?php declare(strict_types = 1);

namespace MailPoet\Test\Doctrine\EventListeners;

use MailPoet\Config\SubscriberChangesNotifier;
use MailPoet\Doctrine\Annotations\AnnotationReaderProvider;
use MailPoet\Doctrine\ArrayCache;
use MailPoet\Doctrine\ConfigurationFactory;
use MailPoet\Doctrine\EntityManagerFactory;
use MailPoet\Doctrine\EventListeners\EmojiEncodingListener;
use MailPoet\Doctrine\EventListeners\LastSubscribedAtListener;
use MailPoet\Doctrine\EventListeners\NewsletterListener;
use MailPoet\Doctrine\EventListeners\SubscriberListener;
use MailPoet\Doctrine\EventListeners\TimestampListener;
use MailPoet\Doctrine\EventListeners\ValidationListener;
use MailPoet\Doctrine\Validator\ValidationException;
use MailPoet\Doctrine\Validator\ValidatorFactory;
use MailPoet\WP\Emoji;
use MailPoet\WP\Functions as WPFunctions;

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
    $this->connection->executeStatement("DROP TABLE IF EXISTS $this->tableName");
    $this->connection->executeStatement("
      CREATE TABLE $this->tableName (
        id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name varchar(255) NOT NULL,
        email varchar(255)
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
      verify($e->getMessage())->same("Validation failed for '$entityClass'.\nDetails:\n  [name] This value should not be blank.");
    }
  }

  public function testItValidatesUpdatedEntity() {
    $id = 1;
    $name = 'Test name';
    $this->connection->executeStatement("INSERT INTO $this->tableName (id, name) VALUES (?, ?)", [$id, $name]);

    /** @var ValidatedEntity $entity */
    $entity = $this->entityManager->find(ValidatedEntity::class, $id);
    $entity->setName('x');
    try {
      $this->entityManager->flush();
      $this->fail('Validation exception was not thrown.');
    } catch (ValidationException $e) {
      $entityClass = get_class($entity);
      verify($e->getMessage())->same("Validation failed for '$entityClass'.\nDetails:\n  [name] This value is too short. It should have 3 characters or more.");
    }
  }

  public function testItSupportsValidationGroups() {
    $id = 1;
    $name = 'Test name';
    $email = 'test@example.com';
    $this->connection->executeStatement("INSERT INTO $this->tableName (id, name, email) VALUES (?, ?, ?)", [$id, $name, $email]);

    /** @var ValidatedEntity $entity */
    $entity = $this->entityManager->find(ValidatedEntity::class, $id);
    $entity->setEmail('example');

    // Validation group is Default, no email validation
    try {
      $this->entityManager->flush();
    } catch (ValidationException $e) {
      $this->fail('Validation exception was thrown.');
    }

    // Validation group is Default + Saving, with email validation
    $entity->setValidationGroups(['Saving', 'Default']);
    $entity->setEmail('example2');

    try {
      $this->entityManager->flush();
      $this->fail('Validation exception was not thrown.');
    } catch (ValidationException $e) {
      $entityClass = get_class($entity);
      verify($e->getMessage())->same("Validation failed for '$entityClass'.\nDetails:\n  [email] This value is not a valid email address.");
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
    $newsletterListener = new NewsletterListener($this->wp);
    $subscriberListener = new SubscriberListener(new SubscriberChangesNotifier($this->wp));
    $entityManagerFactory = new EntityManagerFactory(
      $this->connection,
      $configuration,
      $timestampListener,
      $validationListener,
      $emojiEncodingListener,
      $lastSubscribedAtListener,
      $newsletterListener,
      $subscriberListener
    );
    return $entityManagerFactory->createEntityManager();
  }
}
