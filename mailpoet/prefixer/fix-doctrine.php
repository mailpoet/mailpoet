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

// Replace PHP8 version of Doctrine/Common/Cache/Psr6/TypedCacheItem.php with PHP7 version
// This is needed so that we pass pre-commit checks in the plugin repository.
$php7CachedItem = file_get_contents(__DIR__ . "/../vendor-prefixed/doctrine/cache/lib/Doctrine/Common/Cache/Psr6/CacheItem.php");
$php7CachedItem = str_replace('final class CacheItem', 'final class TypedCacheItem', $php7CachedItem);
file_put_contents(__DIR__ . "/../vendor-prefixed/doctrine/cache/lib/Doctrine/Common/Cache/Psr6/TypedCacheItem.php", $php7CachedItem);

// Replace PHP8 syntax in ReflectionReadonlyProperty.
// The class is used only in PHP8.1 but it fail to pass pre-commit checks in the plugin repository
// See https://github.com/doctrine/orm/commit/580b9196e65adaacc05b9f7a50654739ad995597#diff-732e324167dd49e48b221477c3e0f6d7934f3eb5ec0970dbf1c06f6c7df15398R3790-R3793
$readonlyProxy = file_get_contents(__DIR__ . "/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Mapping/ReflectionReadonlyProperty.php");
$readonlyProxy = str_replace(
  [
    'public function __construct(private ReflectionProperty $wrappedProperty)',
    'parent::__construct($wrappedProperty->class, $wrappedProperty->name);',
  ],
  [
    "/** @var ReflectionProperty */\n    private \$wrappedProperty;\n\n    public function __construct(ReflectionProperty \$wrappedProperty)",
    "\$this->wrappedProperty = \$wrappedProperty;\n    parent::__construct(\$wrappedProperty->class, \$wrappedProperty->name);",
  ],
  $readonlyProxy
);
file_put_contents(__DIR__ . "/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Mapping/ReflectionReadonlyProperty.php", $readonlyProxy);


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
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Connections');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Driver/AbstractOracleDriver');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Driver/DrizzlePDOMySql');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Driver/IBMDB2');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Driver/Mysqli');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Driver/OCI8');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Driver/PDOIbm');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Driver/PDOOracle');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Driver/PDOPgSql');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Driver/PDOSqlite');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Driver/PDOSqlsrv');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Driver/SQLAnywhere');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Driver/SQLSrv');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Schema');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Sharding');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Tools');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Driver/AbstractDB2Driver.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Driver/AbstractOracleDriver.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Driver/AbstractPostgreSQLDriver.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Driver/AbstractSQLAnywhereDriver.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Driver/AbstractSQLiteDriver.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Driver/AbstractSQLServerDriver.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Event/ConnectionEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Event/Listeners/MysqlSessionInit.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Event/Listeners/OracleSessionInit.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Event/SchemaAlterTableAddColumnEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Event/SchemaAlterTableChangeColumnEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Event/SchemaAlterTableEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Event/SchemaAlterTableRemoveColumnEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Event/SchemaAlterTableRenameColumnEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Event/SchemaColumnDefinitionEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Event/SchemaCreateTableColumnEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Event/SchemaCreateTableEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Event/SchemaDropTableEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Event/SchemaEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Event/SchemaIndexDefinitionEventArgs.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/DB2Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/DrizzlePlatform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/Keywords/DB2Keywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/Keywords/DrizzleKeywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/Keywords/OracleKeywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/Keywords/PostgreSQL91Keywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/Keywords/PostgreSQL92Keywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/Keywords/PostgreSQL94Keywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/Keywords/PostgreSQL100Keywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/Keywords/PostgreSQLKeywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/Keywords/SQLAnywhere11Keywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/Keywords/SQLAnywhere12Keywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/Keywords/SQLAnywhere16Keywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/Keywords/SQLAnywhereKeywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/Keywords/SQLiteKeywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/Keywords/SQLServer2005Keywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/Keywords/SQLServer2008Keywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/Keywords/SQLServer2012Keywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/Keywords/SQLServerKeywords.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/OraclePlatform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/PostgreSQL91Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/PostgreSQL92Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/PostgreSQL94Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/PostgreSQL100Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/PostgreSqlPlatform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLAnywhere11Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLAnywhere12Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLAnywhere16Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLAnywherePlatform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLAzurePlatform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SqlitePlatform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLServer2005Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLServer2008Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLServer2012Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLServerPlatform.php');

// cleanup Doctrine ORM
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/bin');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/ci');
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
