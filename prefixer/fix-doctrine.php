<?php

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

// fix '"continue" targeting switch is equivalent to "break". Did you mean to use "continue 2" on PHP 7.3+'
$file = __DIR__ . '/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/UnitOfWork.php';
$data = file_get_contents($file);
if (strpos($data, '// mp-fixed') === false) {
  $data = preg_replace('#(// use the entity association.+?if.+?{.+?)continue;#s', '$1break; // mp-fixed', $data);
  $data = preg_replace('#(if\s+\(!\$associatedId\)\s+{\s+// Foreign key is NULL.+?)continue;#s', '$1break; // mp-fixed', $data);
  file_put_contents($file, $data);
}

// apply https://github.com/doctrine/common/commit/59374594248862ccfb418bbb5fc2cf91c5ef8dd0#diff-ce03ab9b396edcbb313c54234c20e0de
// to our version of Doctrine - when we can upgrade to Doctrine\Common >= v2.11.0, this patch can be removed
$file = __DIR__ . '/../vendor-prefixed/doctrine/common/lib/Doctrine/Common/Proxy/ProxyGenerator.php';
$data = file_get_contents($file);
$data = str_replace('$code = \\file($method->getDeclaringClass()->getFileName());', '$code = \\file($method->getFileName());', $data);
file_put_contents($file, $data);

// apply https://github.com/doctrine/orm/pull/7785/files
// to our version of Doctrine - when we can upgrade to Doctrine\ORM >= v2.6.0, this patch can be removed
$file = __DIR__ . '/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Query/Parser.php';
$data = file_get_contents(__DIR__ . '/Parser.php');
file_put_contents($file, $data);

// cleanup file types by extension
exec('find ' . __DIR__ . "/../vendor-prefixed/doctrine -type f -name '*.xsd' -delete");
exec('find ' . __DIR__ . "/../vendor-prefixed/doctrine -type f -name 'phpstan.neon' -delete");
exec('find ' . __DIR__ . "/../vendor-prefixed/doctrine -type f -name 'build.xml' -delete");
exec('find ' . __DIR__ . "/../vendor-prefixed/doctrine -type f -name 'build.properties' -delete");
exec('find ' . __DIR__ . "/../vendor-prefixed/doctrine -type f -name 'UPGRADE_*' -delete");
exec('find ' . __DIR__ . "/../vendor-prefixed/doctrine -type f -name 'README.markdown' -delete");

// cleanup Doctrine Cache
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/cache/lib/Doctrine/Common/Cache/ApcCache.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/cache/lib/Doctrine/Common/Cache/ApcuCache.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/cache/lib/Doctrine/Common/Cache/ChainCache.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/cache/lib/Doctrine/Common/Cache/CouchbaseCache.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/cache/lib/Doctrine/Common/Cache/MemcacheCache.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/cache/lib/Doctrine/Common/Cache/MemcachedCache.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/cache/lib/Doctrine/Common/Cache/MongoDBCache.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/cache/lib/Doctrine/Common/Cache/PhpFileCache.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/cache/lib/Doctrine/Common/Cache/PredisCache.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/cache/lib/Doctrine/Common/Cache/RedisCache.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/cache/lib/Doctrine/Common/Cache/RiakCache.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/cache/lib/Doctrine/Common/Cache/SQLite3Cache.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/cache/lib/Doctrine/Common/Cache/VoidCache.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/cache/lib/Doctrine/Common/Cache/WinCacheCache.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/cache/lib/Doctrine/Common/Cache/XcacheCache.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/cache/lib/Doctrine/Common/Cache/ZendDataCache.php');

// cleanup Doctrine DBAL
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/bin');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Connections');
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
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/PostgreSqlPlatform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLAnywhere11Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLAnywhere12Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLAnywhere16Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLAnywherePlatform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLAzurePlatform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLitePlatform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLServer2005Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLServer2008Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLServer2012Platform.php');
exec('rm ' . __DIR__ . '/../vendor-prefixed/doctrine/dbal/lib/Doctrine/DBAL/Platforms/SQLServerPlatform.php');

// cleanup Doctrine ORM
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/bin');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/docs');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DatabaseDriver.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/SimplifiedXmlDriver.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/SimplifiedYamlDriver.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/XmlDriver.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/YamlDriver.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/orm/lib/Doctrine/ORM/Tools');

// cleanup Doctrine deps
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/inflector');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/doctrine/lexer/docs');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/console');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/symfony/debug');
