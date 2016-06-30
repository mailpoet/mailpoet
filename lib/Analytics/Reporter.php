<?php
namespace MailPoet\Analytics;

class Reporter {

  private $fields = array(
    'Plugin Version' => 'pluginVersion',
  );

  function getData() {
    $_this = $this;

    $analytics_data = array_map(function($func) use ($_this) {
      return $_this->$func();
    }, $this->fields);

    return $analytics_data;
  }

  private function pluginVersion() {
    return MAILPOET_VERSION;
  }
}
