<?php
namespace MailPoet\Cron;

if(!defined('ABSPATH')) exit;

class Supervisor {
  public $daemon;
  public $token;
  public $force_run;

  function __construct($force_run = false) {
    $this->daemon = CronHelper::getDaemon();
    $this->token = CronHelper::createToken();
    $this->force_run = $force_run;
  }

  function checkDaemon() {
    $daemon = $this->daemon;
    if(!$daemon) {
      $daemon = CronHelper::createDaemon($this->token);
      return $this->runDaemon($daemon);
    }
    // if the daemon is stopped, return its status and do nothing
    if(!$this->force_run &&
      isset($daemon['status']) &&
      $daemon['status'] === Daemon::STATUS_STOPPED
    ) {
      return $this->formatDaemonStatusMessage($daemon['status']);
    }
    $elapsed_time = time() - (int)$daemon['updated_at'];
    // if it's been less than 40 seconds since last execution and we're not
    // force-running the daemon, return its status and do nothing
    if($elapsed_time < CronHelper::DAEMON_EXECUTION_TIMEOUT && !$this->force_run) {
      return $this->formatDaemonStatusMessage($daemon['status']);
    }
    // if it's been less than 40 seconds since last execution, we are
    // force-running the daemon and it's either being started or stopped,
    // return its status and do nothing
    elseif($elapsed_time < CronHelper::DAEMON_EXECUTION_TIMEOUT &&
      $this->force_run &&
      in_array($daemon['status'], array(
        Daemon::STATUS_STOPPING,
        Daemon::STATUS_STARTING
      ))
    ) {
      return $this->formatDaemonStatusMessage($daemon['status']);
    }
    // re-create (restart) daemon
    CronHelper::createDaemon($this->token);
    return $this->runDaemon();
  }

  function runDaemon() {
    $request = CronHelper::accessDaemon($this->token);
    preg_match('/\[(mailpoet_cron_error:.*?)\]/i', $request, $status);
    $daemon = CronHelper::getDaemon();
    if(!empty($status) || !$daemon) {
      if(!$daemon) {
        $message = __('Daemon failed to run.');
      } else {
        list(, $message) = explode(':', $status[0]);
        $message = base64_decode($message);
      }
      return $this->formatResultMessage(
        false,
        $message
      );
    }
    return $this->formatDaemonStatusMessage($daemon['status']);
  }

  private function formatDaemonStatusMessage($status) {
    return $this->formatResultMessage(
      true,
      sprintf(
        __('Daemon is currently %s.'),
        __($status)
      )
    );
  }

  private function formatResultMessage($result, $message) {
    $formattedResult = array(
      'result' => $result
    );
    if(!$result) {
      $formattedResult['errors'] = array($message);
    } else {
      $formattedResult['message'] = $message;
    }
    return $formattedResult;
  }
}