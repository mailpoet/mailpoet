<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

// Fix PHPStan variables import. The PHP-Scoper can't handle it
//* @phpstan-import-type Level from \MailPoetVendor\Monolog\Logger
//* @phpstan-import-type LevelName from \MailPoetVendor\Monolog\Logger
$file = __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/AbstractHandler.php';
$content = file_get_contents($file);
$content = str_replace(
  [
    '@phpstan-import-type Level from \Monolog\Logger',
    '@phpstan-import-type LevelName from \Monolog\Logger',
  ],
  [
    '@phpstan-import-type Level from \MailPoetVendor\Monolog\Logger',
    '@phpstan-import-type LevelName from \MailPoetVendor\Monolog\Logger',
  ],
  $content
);
file_put_contents($file, $content);

// cleanup unused Handlers
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/AbstractSyslogHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/AmqpHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/BrowserConsoleHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/BufferHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/ChromePHPHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/CouchDBHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/CubeHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/Curl');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/DeduplicationHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/DoctrineCouchDBHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/DynamoDbHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/ElasticsearchHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/ElasticaHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/ErrorLogHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/FilterHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/FingersCrossed');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/FingersCrossedHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/FirePHPHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/FleepHookHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/FlowdockHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/GelfHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/GroupHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/HandlerWrapper.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/IFTTTHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/InsightOpsHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/LogEntriesHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/LogglyHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/LogmaticHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/MailHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/MandrillHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/MissingExtensionException.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/MongoDBHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/NativeMailerHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/NewRelicHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/NullHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/PHPConsoleHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/PsrHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/PushoverHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/RedisHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/RedisPubSubHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/RollbarHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/RotatingFileHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/SamplingHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/SendGridHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/Slack');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/SlackHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/SlackWebhookHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/SocketHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/SqsHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/StreamHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/SwiftMailerHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/SyslogHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/SyslogUdp');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/SyslogUdpHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/TelegramBotHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/TestHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/WhatFailureGroupHandler.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Handler/ZendMonitorHandler.php');

// cleanup unused Formatters
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Formatter/ChromePHPFormatter.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Formatter/ElasticaFormatter.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Formatter/ElasticsearchFormatter.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Formatter/FlowdockFormatter.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Formatter/FluentdFormatter.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Formatter/GelfMessageFormatter.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Formatter/HtmlFormatter.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Formatter/LogmaticFormatter.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Formatter/JsonFormatter.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Formatter/LogglyFormatter.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Formatter/LogstashFormatter.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Formatter/MongoDBFormatter.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Formatter/ScalarFormatter.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Formatter/WildfireFormatter.php');

// cleanup unused Processors
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Processor/GitProcessor.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Processor/MemoryPeakUsageProcessor.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Processor/MercurialProcessor.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Processor/ProcessIdProcessor.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Processor/PsrLogMessageProcessor.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Processor/TagProcessor.php');
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Processor/UidProcessor.php');

// cleanup unused Attributes
exec('rm -r ' . __DIR__ . '/../vendor-prefixed/monolog/monolog/src/Monolog/Attribute/AsMonologProcessor.php');
