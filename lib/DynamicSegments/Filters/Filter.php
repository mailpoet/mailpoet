<?php

namespace MailPoet\DynamicSegments\Filters;

use MailPoetVendor\Idiorm\ORM;

interface Filter {

  function toSql(ORM $orm);

  function toArray();

}
