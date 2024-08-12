<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Config\Env;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class DatabaseEngineNotice {
  const OPTION_NAME = 'database-engine-notice';
  const DISMISS_NOTICE_TIMEOUT_SECONDS = 15_552_000; // 6 months
  const MAX_TABLES_TO_DISPLAY = 2;

  private WPFunctions $wp;

  private EntityManager $entityManager;

  public function __construct(
    WPFunctions $wp,
    EntityManager $entityManager
  ) {
    $this->wp = $wp;
    $this->entityManager = $entityManager;
  }

  //TODO: check only once a day
  public function init($shouldDisplay): ?Notice {
    if (!$shouldDisplay || $this->wp->getTransient(self::OPTION_NAME)) {
      return null;
    }

    $tablesWithIncorrectEngine = $this->checkTableEngines();
    if ($tablesWithIncorrectEngine === []) {
      return null;
    }

    return $this->display($tablesWithIncorrectEngine);
  }

  /**
   * Returns a list of table names that are not using the InnoDB engine.
   */
  private function checkTableEngines(): array {
    $data = $this->entityManager->getConnection()->executeQuery(
      'SHOW TABLE STATUS WHERE Name LIKE :prefix',
      [
        'prefix' => Env::$dbPrefix . '_%',
      ]
    )->fetchAllAssociative();

    return array_map(
      fn($row) => $row['Name'],
      array_filter(
        $data,
        fn($row) => strtolower($row['Engine']) !== 'innodb'
      )
    );
  }

  private function display(array $tablesWithIncorrectEngine): ?Notice {
    // translators: %s is the list of the table names
    $errorString = __('Some of the MailPoet plugin’s tables are not using the InnoDB engine (%s). This may cause performance and compatibility issues. Please ensure all MailPoet tables are converted to use the InnoDB engine. For more information, check out [link]this guide[/link].', 'mailpoet');
    $tables = $this->formatTableNames($tablesWithIncorrectEngine);
    $errorString = sprintf($errorString, $tables);
    $error = Helpers::replaceLinkTags($errorString, 'https://kb.mailpoet.com/article/200-solving-database-connection-issues#database-configuration', [
      'target' => '_blank',
    ]);

    $extraClasses = 'mailpoet-dismissible-notice is-dismissible';

    return Notice::displayWarning($error, $extraClasses, self::OPTION_NAME);
  }

  private function formatTableNames(array $tablesWithIncorrectEngine): string {
    sort($tablesWithIncorrectEngine);

    $tables = array_map(
      fn($table) => "“${table}”",
      array_slice($tablesWithIncorrectEngine, 0, self::MAX_TABLES_TO_DISPLAY)
    );

    $remainingTablesCount = count($tablesWithIncorrectEngine) - count($tables);
    if ($remainingTablesCount > 0) {
      // translators: %d is the number of remaining tables, the whole string will be: "table1, table2 and 3 more"
      $tables[] = sprintf(__('and %d more', 'mailpoet'), $remainingTablesCount);
    }

    return implode(', ', $tables);
  }

  public function disable() {
    $this->wp->setTransient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }
}
