<?php declare(strict_types = 1);

namespace MailPoet\Test\DataGenerator;

use Codeception\Lib\Console\Output;
use MailPoet\Test\DataGenerator\Generators\WooCommercePastRevenues;

class DataGenerator {

  const PAST_REVENUES_GENERATOR = 'past_revenues';

  /** @var Output */
  private $console;

  public function __construct(
    Output $console
  ) {
    $this->console = $console;
  }

  public function run($generatorName) {
    if (!$generatorName) $generatorName = self::PAST_REVENUES_GENERATOR;
    ini_set('memory_limit', '1024M');
    $timer = time();
    try {
      $generator = $this->createGenerator($generatorName);
      foreach ($generator->generate() as $message) {
        $this->log($timer, $message);
      }
    } catch (\Exception $e) {
      $this->console->exception($e);
    }
    $this->log($timer, 'DONE!');
  }

  public function runBefore($generatorName = null) {
    if (!$generatorName) $generatorName = self::PAST_REVENUES_GENERATOR;
    $this->createGenerator($generatorName)->runBefore();
  }

  public function runAfter($generatorName = null) {
    if (!$generatorName) $generatorName = self::PAST_REVENUES_GENERATOR;
    $this->createGenerator($generatorName)->runAfter();
  }

  private function createGenerator($generatorName) {
    switch ($generatorName) {
      case self::PAST_REVENUES_GENERATOR:
        return new WooCommercePastRevenues();
      default:
        throw new \Exception("Missing or unknown generator name. Possible values: \n " . self::PAST_REVENUES_GENERATOR);
    }
  }

  private function log($timer, $message) {
    $duration = time() - $timer;
    $memory = round(memory_get_usage() / 1048576);
    $this->console->message("[{$duration}s][{$memory}MB] $message")->writeln();
  }
}
