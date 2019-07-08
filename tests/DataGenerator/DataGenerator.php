<?php

namespace MailPoet\Test\DataGenerator;

use Codeception\Lib\Console\Output;
use MailPoet\Test\DataGenerator\Generators\GeneratorHelper;
use MailPoet\Test\DataGenerator\Generators\WooCommercePastRevenues;

class DataGenerator {

  const PAST_REVENUES_GENERATOR = 'past_revenues';

  /** @var Output */
  private $console;

  function __construct(Output $console) {
    $this->console = $console;
  }

  function run($generator_name) {
    ini_set('memory_limit','1024M');
    $timer = time();
    try {
      $generator = $this->createGenerator($generator_name);
      foreach ($generator->generate() as $message) {
        $this->log($timer, $message);
      }
    } catch (\Exception $e) {
      $this->console->exception($e);
    }
    $this->log($timer, 'DONE!');
  }

  private function createGenerator($generator_name) {
    switch ($generator_name) {
      case self::PAST_REVENUES_GENERATOR:
        return new WooCommercePastRevenues();
      default:
        throw new \Exception("Missing or unknown generator name. Possible values: \n " . self::PAST_REVENUES_GENERATOR);
    }
  }

  private function log($timer, $message) {
    $duration = time() - $timer;
    $memory = round(memory_get_usage()/1048576);
    $this->console->message("[{$duration}s][{$memory}MB] $message")->writeln();
  }
}
