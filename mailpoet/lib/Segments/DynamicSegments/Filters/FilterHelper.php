<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Util\Security;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class FilterHelper {
    /** @var EntityManager */
  private $entityManager;

  public function __construct(
    EntityManager $entityManager
  ) {
    $this->entityManager = $entityManager;
  }

  public function getPrefixedTable(string $table): string {
    global $wpdb;
    return sprintf('%s%s', $wpdb->prefix, $table);
  }

  public function getNewSubscribersQueryBuilder(): QueryBuilder {
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select('id')
      ->from($this->getSubscribersTable());
  }

  public function getSubscribersTable(): string {
    return $this->entityManager
      ->getClassMetadata(SubscriberEntity::class)
      ->getTableName();
  }

  public function getInterpolatedSQL(QueryBuilder $query): string {
    $sql = $query->getSQL();
    $params = $query->getParameters();
    $search = array_map(function($key) {
      return ":$key";
    }, array_keys($params));
    $replace = array_map(function($value) use ($query) {
      if (is_array($value)) {
        $quotedValues = array_map(function($arrayValue) use ($query) {
          return $query->expr()->literal($arrayValue);
        }, $value);
        return implode(',', $quotedValues);
      }
      return $query->expr()->literal($value);
    }, array_values($params));
    return str_replace($search, $replace, $sql);
  }

  public function getUniqueParameterName(string $parameter): string {
    $suffix = Security::generateRandomString();
    return sprintf("%s_%s", $parameter, $suffix);
  }
}
