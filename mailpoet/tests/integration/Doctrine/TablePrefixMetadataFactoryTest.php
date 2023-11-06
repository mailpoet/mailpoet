<?php declare(strict_types = 1);

namespace MailPoet\Doctrine;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\WpPostEntity;

class TablePrefixMetadataFactoryTest extends \MailPoetTest {
  public function testItPrefixTablesCorrectly() {
    $wpPostMetadata = $this->entityManager->getClassMetadata(WpPostEntity::class);
    $newslettersMetadata = $this->entityManager->getClassMetadata(NewsletterEntity::class);
    verify($wpPostMetadata->getTableName())->equals('mp_posts');
    verify($newslettersMetadata->getTableName())->equals('mp_mailpoet_newsletters');
  }
}
