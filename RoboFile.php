<?php
class RoboFile extends \Robo\Tasks {
  function update() {
    $this->_exec('./composer.phar update');
  }
}
