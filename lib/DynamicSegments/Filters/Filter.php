<?php

namespace MailPoet\DynamicSegments\Filters;

use MailPoetVendor\Idiorm\ORM;

interface Filter {
  public function toSql(ORM $orm);

  public function toArray();
}
