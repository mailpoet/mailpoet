<?php
/**
 * Copied from https://github.com/Codeception/robo-paracept/blob/master/src/SplitTestsByGroups.php
 * The package is abandoned and we can no longer install it via Composer
 */

namespace MailPoet\Test\SplitTests;

trait SplitTestFilesByGroups {
  /**
   * @param $numGroups
   * @return SplitTestFilesByGroupsTask
   */
  protected function taskSplitTestFilesByGroups($numGroups) {
    return $this->task(SplitTestFilesByGroupsTask::class, $numGroups);
  }
}
