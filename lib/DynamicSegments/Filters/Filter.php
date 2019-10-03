<?php

namespace MailPoet\Premium\DynamicSegments\Filters;

interface Filter {

  function toSql(\ORM $orm);

  function toArray();

}