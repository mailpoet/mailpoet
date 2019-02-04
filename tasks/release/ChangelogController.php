<?php

namespace MailPoetTasks\Release;

require_once __DIR__ . '/Jira.php';

class ChangelogController {

  const FALLBACK_RECORD = "* Improved: minor changes and fixes.\n";
  const HEADING_GLUE = ' - ';

  /** @var string */
  private $readme_file;

  /** @var Jira */
  private $jira;

  function __construct(Jira $jira, $readme_file) {
    $this->jira = $jira;
    $this->readme_file = $readme_file;
  }

  static function createWithJiraCredentials($token, $user, $project, $readme_file) {
    return new self(new Jira($token, $user, $project), $readme_file);
  }

  function update($version_name = null) {
    $changelog_data = $this->get($version_name);
    $this->updateReadme($changelog_data[0], $changelog_data[1]);
    return $changelog_data;
  }

  function get($version_name = null) {
    $version = $this->jira->getVersion($version_name);
    $issues = $this->jira->getIssuesDataForVersion($version);
    $heading = $this->renderHeading($version);
    $changelog = $this->renderList($issues, Jira::CHANGELOG_FIELD_ID);
    if(!$changelog) {
      $changelog = self::FALLBACK_RECORD;
    }
    $notes = $this->renderList($issues, Jira::RELEASENOTE_FIELD_ID);
    return [$heading, $changelog, $notes];
  }

  private function renderHeading(array $version) {
    $date = $version['releaseDate'] ?: date('Y-m-d');
    return "= {$version['name']}" . self::HEADING_GLUE .  "$date =";
  }

  private function renderList(array $issues, $field) {
    $messages = [];
    foreach($issues as $issue) {
      if(!isset($issue['fields'][$field])) {
        continue;
      }
      $messages[] = "* " . $this->sanitizePunctuation($issue['fields'][$field], ';');
    }
    if(empty($messages)) {
      return null;
    }
    $list = implode("\n", $messages);
    return empty($list) ? $list : $this->sanitizePunctuation($list, '.');
  }

  private function sanitizePunctuation($message, $fallback) {
    $valid_punctuation = ['?','.','!'];
    $message = rtrim($message, ';, ');
    if(!in_array(substr($message, -1), $valid_punctuation)) {
      $message .= $fallback;
    }
    return $message;
  }

  private function updateReadme($heading, $changes_list) {
    $heading_prefix = explode(self::HEADING_GLUE, $heading)[0];
    $readme = file_get_contents($this->readme_file);
    $changelog = "$heading\n$changes_list";

    if(strpos($readme, $heading_prefix) !== false) {
      $start = preg_quote($heading_prefix);
      $readme = preg_replace("/$start.*?(?:\r*\n){2}/us", "$changelog\n\n", $readme);
    } else {
      $readme = preg_replace("/== Changelog ==\n/u", "== Changelog ==\n\n$changelog\n", $readme);
    }
    file_put_contents($this->readme_file, $readme);
  }
}
