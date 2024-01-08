<?php declare(strict_types = 1);

namespace MailPoet\Doctrine;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\WpPostEntity;

class TablePrefixMetadataFactoryTest extends \MailPoetTest {
  public function testItPrefixTablesCorrectly() {
    global $wpdb;
    $dbPrefix = $wpdb->prefix;
    $wpPostMetadata = $this->entityManager->getClassMetadata(WpPostEntity::class);
    $newslettersMetadata = $this->entityManager->getClassMetadata(NewsletterEntity::class);
    verify($wpPostMetadata->getTableName())->equals($dbPrefix . 'posts');
    verify($newslettersMetadata->getTableName())->equals($dbPrefix . 'mailpoet_newsletters');
  }
}
