<?php

use MailPoet\Migrator\Migrator;

throw new Exception(sprintf('This file should not be processed by %s.', Migrator::class));
