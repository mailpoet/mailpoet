<?php

namespace MailPoet\DynamicSegments\Filters;

interface Filter {

  function toSql(\ORM $orm);

  function toArray();

}
