<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

// fix Doctrine namespaces in string not being correctly prefixed
$iterator = new RecursiveDirectoryIterator(__DIR__ . '/../vendor-prefixed/doctrine', RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
foreach ($files as $file) {
  if (substr($file, -3) === 'php') {
    $data = file_get_contents($file);
    $data = str_replace('\'Doctrine\\\\', '\'MailPoetVendor\\\\Doctrine\\\\', $data);
    $data = str_replace('"Doctrine\\\\', '"MailPoetVendor\\\\Doctrine\\\\', $data);
    $data = str_replace(' \\Doctrine\\', ' \\MailPoetVendor\\Doctrine\\', $data);
    $data = str_replace('* @var array<\\Doctrine\\', '* @var array<\\MailPoetVendor\\Doctrine\\', $data);
    file_put_contents($file, $data);
  }
}

// cleanup file types by extension
exec('find ' . __DIR__ . "/../vendor-prefixed/doctrine -type f -name '*.xsd' -delete");
exec('find ' . __DIR__ . "/../vendor-prefixed/doctrine -type f -name 'phpstan*.neon' -delete");
exec('find ' . __DIR__ . "/../vendor-prefixed/doctrine -type f -name 'build.xml' -delete");
exec('find ' . __DIR__ . "/../vendor-prefixed/doctrine -type f -name 'psalm.xml' -delete");
exec('find ' . __DIR__ . "/../vendor-prefixed/doctrine -type f -name 'build.properties' -delete");
exec('find ' . __DIR__ . "/../vendor-prefixed/doctrine -type f -name 'UPGRADE_*' -delete");
exec('find ' . __DIR__ . "/../vendor-prefixed/doctrine -type f -name 'README.markdown' -delete");

// cleanup Doctrine DBAL
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/bin');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Connections');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Driver/AbstractOracleDriver');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Driver/IBMDB2');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Driver/Mysqli');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Driver/OCI8');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Driver/SQLSrv');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Tools');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Driver/AbstractDB2Driver.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Driver/AbstractOracleDriver.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Driver/AbstractPostgreSQLDriver.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Driver/AbstractSQLServerDriver.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Event/ConnectionEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Event/Listeners/OracleSessionInit.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Event/SchemaAlterTableAddColumnEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Event/SchemaAlterTableChangeColumnEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Event/SchemaAlterTableEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Event/SchemaAlterTableRemoveColumnEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Event/SchemaAlterTableRenameColumnEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Event/SchemaColumnDefinitionEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Event/SchemaCreateTableColumnEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Event/SchemaCreateTableEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Event/SchemaDropTableEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Event/SchemaEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Event/SchemaIndexDefinitionEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Platforms/DB2Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Platforms/Keywords/DB2Keywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Platforms/Keywords/OracleKeywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Platforms/Keywords/PostgreSQL94Keywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Platforms/Keywords/PostgreSQL100Keywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Platforms/Keywords/PostgreSQLKeywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Platforms/Keywords/SQLiteKeywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Platforms/Keywords/SQLServer2012Keywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Platforms/Keywords/SQLServerKeywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Platforms/OraclePlatform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Platforms/PostgreSQL94Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Platforms/PostgreSQL100Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Platforms/PostgreSqlPlatform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Platforms/SqlitePlatform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Platforms/SQLServer2012Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/src/Platforms/SQLServerPlatform.php');

// cleanup Doctrine ORM
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/bin');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DatabaseDriver.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/SimplifiedXmlDriver.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/SimplifiedYamlDriver.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/XmlDriver.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/YamlDriver.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Tools/Console');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Tools/Event');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Tools/Export');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Tools/*.php');

// cleanup Doctrine deps
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/inflector');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/collections/docs');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/common/docs');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/deprecations/lib/Doctrine/Deprecations/PHPUnit');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/deprecations/test_fixtures');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/deprecations/phpcs.xml');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/instantiator/docs');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/persistence/tests_php74');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/persistence/tests_php81');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/console');

// Removing #[\SensitiveParameter] attribute because it can break the plugin on PHP 7.4
$attributeReplacement = [
  'find' => [
    '#[\SensitiveParameter]',
  ],
  'replace' => [
    '',
  ],
];
$files = [
  '../vendor-prefixed/doctrine/dbal/src/Connection.php',
  '../vendor-prefixed/doctrine/dbal/src/Driver.php',
  '../vendor-prefixed/doctrine/dbal/src/Driver/AbstractSQLiteDriver/Middleware/EnableForeignKeys.php',
  '../vendor-prefixed/doctrine/dbal/src/Driver/Middleware/AbstractDriverMiddleware.php',
  '../vendor-prefixed/doctrine/dbal/src/Driver/PDO/MySQL/Driver.php',
  '../vendor-prefixed/doctrine/dbal/src/Driver/PDO/OCI/Driver.php',
  '../vendor-prefixed/doctrine/dbal/src/Driver/PDO/PgSQL/Driver.php',
  '../vendor-prefixed/doctrine/dbal/src/Driver/PDO/SQLSrv/Driver.php',
  '../vendor-prefixed/doctrine/dbal/src/Driver/PDO/SQLite/Driver.php',
  '../vendor-prefixed/doctrine/dbal/src/Driver/PgSQL/Driver.php',
  '../vendor-prefixed/doctrine/dbal/src/Driver/SQLite3/Driver.php',
  '../vendor-prefixed/doctrine/dbal/src/DriverManager.php',
  '../vendor-prefixed/doctrine/dbal/src/Exception.php',
  '../vendor-prefixed/doctrine/dbal/src/Logging/Driver.php',
  '../vendor-prefixed/doctrine/dbal/src/Portability/Driver.php',
];

$replacements = [
  [
    'file' => '../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Mapping/ReflectionReadonlyProperty.php',
    'find' => [
      'private ReflectionProperty',
    ],
    'replace' => [
      'ReflectionProperty',
    ],
  ],
];

foreach ($files as $file) {
  $replacements[] = [
    'file' => $file,
    'find' => $attributeReplacement['find'],
    'replace' => $attributeReplacement['replace'],
  ];
}

foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}
