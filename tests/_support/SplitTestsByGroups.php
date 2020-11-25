<?php
// phpcs:ignoreFile
/**
 * Copied from https://github.com/Codeception/robo-paracept/blob/master/src/SplitTestsByGroups.php
 * The package is abandoned and we can no longer install it via Composer
 */

namespace Codeception\Task;

use Codeception\Test\Descriptor as TestDescriptor;
use Codeception\Test\Loader as TestLoader;
use PHPUnit\Framework\DataProviderTestSuite;
use PHPUnit_Framework_TestSuite_DataProvider as DataProvider;
use Robo\Contract\TaskInterface;
use Robo\Exception\TaskException;
use Robo\Task\BaseTask;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

trait SplitTestsByGroups {
  /**
   * @param $numGroups
   *
   * @return SplitTestsByGroupsTask
   */
  protected function taskSplitTestsByGroups($numGroups) {
    return $this->task(SplitTestsByGroupsTask::class, $numGroups);
  }

  /**
   * @param $numGroups
   *
   * @return SplitTestFilesByGroupsTask
   */
  protected function taskSplitTestFilesByGroups($numGroups) {
    return $this->task(SplitTestFilesByGroupsTask::class, $numGroups);
  }
}

abstract class TestsSplitter extends BaseTask {
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

  /**
   * @param       $item
   * @param array $items
   * @param array $resolved
   * @param array $unresolved
   *
   * @return array
   */
  protected function resolveDependencies($item, array $items, array $resolved, array $unresolved) {
    $unresolved[] = $item;
    foreach ($items[$item] as $dep) {
      if (!in_array($dep, $resolved)) {
        if (!in_array($dep, $unresolved)) {
          $unresolved[] = $dep;
          list($resolved, $unresolved) = $this->resolveDependencies($dep, $items, $resolved, $unresolved);
        } else {
          throw new \RuntimeException("Circular dependency: $item -> $dep");
        }
      }
    }
    // Add $item to $resolved if it's not already there
    if (!in_array($item, $resolved)) {
      $resolved[] = $item;
    }
    // Remove all occurrences of $item in $unresolved
    while (($index = array_search($item, $unresolved)) !== false) {
      unset($unresolved[$index]);
    }

    return [$resolved, $unresolved];
  }

  /**
   * Make sure that tests are in array are always with full path and name.
   *
   * @param array $testsListWithDependencies
   *
   * @return array
   */
  protected function resolveDependenciesToFullNames(array $testsListWithDependencies){
    // make sure that dependencies are in array as full names
    foreach ($testsListWithDependencies as $testName => $test) {
      foreach ($test as $i => $dependency) {

        // sometimes it is written as class::method.
        // for that reason we do trim in first case and replace from :: to one in second case


        // just test name, that means that class name is the same, just different method name
        if (strrpos($dependency, ':') === false) {
          $testsListWithDependencies[$testName][$i] = trim(substr($testName,0,strrpos($testName, ':')), ':') . ':' . $dependency;
          continue;
        }
        $dependency = str_replace('::', ':', $dependency);
        // className:testName, that means we need to find proper test.
        list($targetTestFileName, $targetTestMethodName) = explode(':', $dependency);

        // look for proper test in list of all tests. Test could be in different directory so we need to compare
        // strings and if matched we just assign found test name
        foreach (array_keys($testsListWithDependencies) as $arrayKey) {
          if (strpos($arrayKey, $targetTestFileName . '.php:' . $targetTestMethodName) !== false) {
            $testsListWithDependencies[$testName][$i] = $arrayKey;
            continue 2;
          }
        }
        throw new \RuntimeException('Dependency target test '.$dependency.' not found. Please make sure test exists and you are using full test name');
      }
    }
    return $testsListWithDependencies;
  }
}

/**
 * Loads all tests into groups and saves them to groupfile according to pattern.
 *
 * ``` php
 * <?php
 * $this->taskSplitTestsByGroups(5)
 *    ->testsFrom('tests')
 *    ->groupsTo('tests/_log/paratest_')
 *    ->run();
 * ?>
 * ```
 */
class SplitTestsByGroupsTask extends TestsSplitter implements TaskInterface
{
  public function run() {
    if (!class_exists('\Codeception\Test\Loader')) {
      throw new TaskException($this, 'This task requires Codeception to be loaded. Please require autoload.php of Codeception');
    }
    $testLoader = new TestLoader(['path' => $this->testsFrom]);
    $testLoader->loadTests($this->testsFrom);
    $tests = $testLoader->getTests();

    $this->printTaskInfo('Processing ' . count($tests) . ' tests');

    $testsHaveAtLeastOneDependency = false;

    // test preloading (and fetching dependencies) requires dummy DI service.
    $di = new \Codeception\Lib\Di();
    // gather test dependencies and deal with dataproviders
    $testsListWithDependencies = [];
    foreach ($tests as $test) {
      if ($test instanceof DataProvider || $test instanceof DataProviderTestSuite) {
        $test = current($test->tests());
      }

      // load dependencies for cest type. Unit tests dependencies are loaded automatically
      if ($test instanceof \Codeception\Test\Cest) {
        $test->getMetadata()->setServices(['di'=>$di]);
        $test->preload();
      }

      if (method_exists($test, 'getMetadata')) {
        $testsListWithDependencies[TestDescriptor::getTestFullName($test)] = $test->getMetadata()
          ->getDependencies();
        if ($testsHaveAtLeastOneDependency === false and count($test->getMetadata()->getDependencies()) != 0) {
          $testsHaveAtLeastOneDependency = true;
        }

        // little hack to get dependencies from phpunit test cases that are private.
      } elseif ($test instanceof \PHPUnit\Framework\TestCase) {
        $ref = new \ReflectionObject($test);
        do {
          try{
            $property = $ref->getProperty('dependencies');
            $property->setAccessible(true);
            $testsListWithDependencies[TestDescriptor::getTestFullName($test)] = $property->getValue($test);

            if ($testsHaveAtLeastOneDependency === false and count($property->getValue($test)) != 0) {
              $testsHaveAtLeastOneDependency = true;
            }

          } catch (\ReflectionException $e) {
            // go up on level on inheritance chain.
          }
        } while($ref = $ref->getParentClass());

      } else {
        $testsListWithDependencies[TestDescriptor::getTestFullName($test)] = [];
      }
    }

    if ($testsHaveAtLeastOneDependency) {
      $this->printTaskInfo('Resolving test dependencies');

      // make sure that dependencies are in array as full names
      try {
        $testsListWithDependencies = $this->resolveDependenciesToFullNames($testsListWithDependencies);
      } catch (\Exception $e) {
        $this->printTaskError($e->getMessage());
        return false;
      }

      // resolved and ordered list of dependencies
      $orderedListOfTests = [];
      // helper array
      $unresolved = [];

      // Resolve dependencies for each test
      foreach (array_keys($testsListWithDependencies) as $test) {
        try {
          list ($orderedListOfTests, $unresolved) = $this->resolveDependencies($test, $testsListWithDependencies, $orderedListOfTests, $unresolved);
        } catch (\Exception $e) {
          $this->printTaskError($e->getMessage());
          return false;
        }
      }

      // if we don't have any dependencies just use keys from original list.
    } else {
      $orderedListOfTests = array_keys($testsListWithDependencies);
    }

    // for even split, calculate number of tests in each group
    $numberOfElementsInGroup = floor(count($orderedListOfTests) / $this->numGroups);

    $i = 1;
    $groups = [];

    // split tests into files.
    foreach ($orderedListOfTests as $test) {
      // move to the next group ONLY if number of tests is equal or greater desired number of tests in group
      // AND current test has no dependencies AKA: we  are in different branch than previous test
      if (!empty($groups[$i]) AND count($groups[$i]) >= $numberOfElementsInGroup AND $i <= ($this->numGroups-1) AND empty($testsListWithDependencies[$test])) {
        $i++;
      }

      $groups[$i][] = $test;
    }

    // saving group files
    foreach ($groups as $i => $tests) {
      $filename = $this->saveTo . $i;
      $this->printTaskInfo("Writing $filename");
      file_put_contents($filename, implode("\n", $tests));
    }
  }
}

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
class SplitTestFilesByGroupsTask extends TestsSplitter implements TaskInterface
{
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
