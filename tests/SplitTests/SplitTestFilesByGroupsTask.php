<?php
/**
 * Copied from https://github.com/Codeception/robo-paracept/blob/master/src/SplitTestsByGroups.php
 * The package is abandoned and we can no longer install it via Composer
 */

namespace MailPoet\Test\SplitTests;

use Robo\Contract\TaskInterface;
use Robo\Task\BaseTask;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Finds all test files and splits them by group.
 * Unlike `SplitTestsByGroupsTask` does not load them into memory and not requires Codeception to be loaded.
 *
 * ``` php
 * <?php
 * $this->taskSplitTestFilesByGroups(5)
 *    ->testsFrom('tests/unit/Acme')
 *    ->codeceptionRoot('projects/tested')
 *    ->groupsTo('tests/_log/paratest_')
 *    ->run();
 * ?>
 * ```
 */
class SplitTestFilesByGroupsTask extends BaseTask implements TaskInterface {
  protected $numGroups;
  protected $projectRoot = '.';
  protected $testsFrom = 'tests';
  protected $saveTo = 'tests/_data/paracept_';
  protected $excludePath = 'vendor';

  public function __construct($groups) {
    $this->numGroups = $groups;
  }

  public function projectRoot($path) {
    $this->projectRoot = $path;
    return $this;
  }

  public function testsFrom($path) {
    $this->testsFrom = $path;

    return $this;
  }

  public function groupsTo($pattern) {
    $this->saveTo = $pattern;

    return $this;
  }

  public function excludePath($path) {
    $this->excludePath = $path;

    return $this;
  }

  public function run() {
    $files = Finder::create()
      ->followLinks()
      ->name('*Cept.php')
      ->name('*Cest.php')
      ->name('*Test.php')
      ->name('*.feature')
      ->path($this->testsFrom)
      ->in($this->projectRoot ? $this->projectRoot : getcwd())
      ->exclude($this->excludePath);

    $i = 0;
    $groups = [];

    $this->printTaskInfo('Processing ' . count($files) . ' files');
    // splitting tests by groups
    /** @var SplFileInfo $file */
    foreach ($files as $file) {
      $groups[($i % $this->numGroups) + 1][] = $file->getRelativePathname();
      $i++;
    }

    // saving group files
    foreach ($groups as $i => $tests) {
      $filename = $this->saveTo . $i;
      $this->printTaskInfo("Writing $filename");
      file_put_contents($filename, implode("\n", $tests));
    }
  }
}
