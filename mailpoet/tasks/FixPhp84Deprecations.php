<?php declare(strict_types = 1);

use MailPoet\Tasks\Php84DeprecationsFix;

require_once __DIR__ . '/Php84DeprecationsFix.php';

$fixer = new Php84DeprecationsFix(['vendor', 'vendor-prefixed']);
$fixer->run();
