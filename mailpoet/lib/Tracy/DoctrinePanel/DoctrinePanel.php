<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Tracy\DoctrinePanel;

use MailPoetVendor\Doctrine\DBAL\Configuration;
use MailPoetVendor\Doctrine\DBAL\Logging\DebugStack;
use MailPoetVendor\Doctrine\DBAL\Platforms\Keywords\MySQLKeywords;
use MailPoetVendor\Doctrine\ORM\EntityManagerInterface;
use Tracy\Debugger;
use Tracy\IBarPanel;

/**
 * Inspired by: https://componette.com/macfja/tracy-doctrine-sql/
 */
class DoctrinePanel implements IBarPanel {
  /** @var DebugStack */
  private $sqlLogger;

  public function __construct(
    Configuration $doctrineConfiguration
  ) {
    $this->sqlLogger = new DebugStack();
    $doctrineConfiguration->setSQLLogger($this->sqlLogger);
  }

  public function getTab() {
    $queries = $this->sqlLogger->queries;
    $count = count($queries);
    $countSuffix = $count === 1 ? 'query' : 'queries';
    $time = $this->formatTime(array_sum(array_column($queries, 'executionMS')));

    $img = '<img
      src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACUAAAAyCAYAAADbTRIgAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAadEVYdFNvZnR3YXJlAFBhaW50Lk5FVCB2My41LjEwMPRyoQAABONJREFUWEe9WFtsVEUYrtEQE4zRqA8mBqJGHlTWiBdAUXxAH9QEL2gQFJSrGoFtK4Ix3q8J1kIVCREEEjCaqNEY0QcevEIDRKi0SQm2AhZdlaqlu3vO7O454/ef/q175sycy7qHL/nS7cz8//ftnNl/5kxDFIpLM2dZSzLPgj34XMbff8AP8flyHnJyAeELYOAwKDW0wJk89OQBopurTOhYAe/m4enDahxPpv6oMmCiDU7jsHSRz14xCmJulXgYB4tLMhM5ND0Uh2bqT0U8jP0wlv7ih9B6RTiKx2DsQg5PB/j1nQ+hnCIcxUMUxynSAb75VRAaUISj2AFjZ3OKdABjN0GI6pLOgInfw9hoTpEOIDIdpIquM2DilzA2ilOkA4jMBeOWiWF+UFyWOZVTpAOIZBXROFyfz44/hVPUH/mmy8jYi4poHL6KgsxZ6ogCCirWyGQIbFcE43I5p6oPYOYSJP1UEUlKWo8LOGXtyC/LnIaS8BSSJS0JJtLJYganTw7Mzlgk2FWVsF6kk8UtLBMfMDQVgeEbcuPVUqy6T9ovTdf3h7MATmG5aOBx3YUA+ja6ZB5LG5uke6JfDsM5fEDar9ypHRtCOlmMY1kzMEN3YHBJCfbRfv42KSsltlOF4gkpXp+tjQlhFzTPYPkg0DkJg4pKUIClbc+wCw2swVqMvcMW/IChc9DZpwzWUrTOYQcGFGGs5QFtrIFUKoJHajRuqRoUSad7FzswwMpL8UYiY52+PRKzdCUaE2209pNTpdt3kB0YQMYwq7p4A2exJW+WtiqdsWivvEE6R7vYgQE2jK1+UBuvYTttZQ2FpZkz8U/k4jbRfuJ66fz8IzswwC7ENoanNo5q0gxdZyIuv046PT+wAwPiG2uiR9emNI7QfvpmKdYuluLtR6R4a6EUbfPBeV5y0TrXW8iiZTZKwCwp1syT7vE+dmAA6pj9cmSB/YhMfaU0Sis7QVb2bpfSdTlb/eDmeqXVdI1fz89uMhW4wCh/vIpTpIPyJ60+PYV5MjWoNErnYDuHpwO3r9unp9AlU4E3FKd3P4enAzfX49NTKMiUUBpl+fO1HJ4Oyp+1+fQU5shU8LW8+VpZ2b8jnYXef0xaj0/y6/m5m0x9pzSO0F55o/cTFi33S/HmgqHSsO5RLhGLUB7QtuYhbyuh/92/fmVpA8oCZSRy29lEplYrjclJxbN3HysbUC7J0oZGfXwVUcwXkalb1Y5E9LaZDlY2QBQxuw/r4/10sc2MoRMC3dj9rnTGor1iinSOhG/IbmEgziMb5jdyxUUjp4TnlM5o4qUhar9zB45L+7V79PEa4tHdO+QIwGzRXXmcS9cRlndsZmk96Fdmv3C7NtbAAwX1IgSNc5RBRtIBT/viwHB/6/E2c12sgbSWgsfhQta7eH1fGawlvRiY4Bzp9EqJLi6EG9hGEHA7GgP2KgFBori6f+fYxn9wDu1BeZisjzGzAwfN8Bs/GDsPAzuVwABpAdOseEANqnz9XtSRRMej0BvD0uHAwHMREOsOgcqC1TxR2xfBn6BzMUvGA6b0dASuA5NeJ8bhF/TFWSo5UDumIUmXkrRW5pBv/mA28/+vGql+INlMJP0WrGXm9oGPRS7oWkELEwILwXfBdpBe9elCjS7D6O8v4E5wI7iY1o3dfClHx0FDw7+Sb2560wLhYgAAAABJRU5ErkJggg=="
      style="height: 15px"
    />';
    return $img . '<span class="tracy-label" >' . "$count $countSuffix / $time ms" . '</span>';
  }

  public function getPanel() {
    ob_start();
    require __DIR__ . '/doctrine-panel.phtml';
    return ob_get_clean();
  }

  public static function init(EntityManagerInterface $entityManager) {
    Debugger::getBar()->addPanel(new DoctrinePanel($entityManager->getConnection()->getConfiguration()));
  }

  protected function formatSql($sql) {
    // format SELECT queries a bit (wrap long select clauses, wrap lines on some keywords)
    preg_match('/^(SELECT\s+)(.*?)(\s+FROM\s+)(.*?)$/iu', trim($sql), $matches);
    if (count($matches) >= 5) {
      // if SELECT clause over 50 chars, make it wrappable
      $selectHtml = mb_strlen($matches[2]) > 50 ? ($matches[1] . '
        <span class="tracy-toggle tracy-collapsed">...</span>
        <div class="tracy-collapsed" style="padding-left: 10px">' . $matches[2] . '</div>
      ') : ($matches[1] . $matches[2]);
      $fromKeyword = $matches[3];
      $rest = $matches[4];

      // try to match & indent WHERE clause
      $whereHtml = '';
      preg_match('/^(.*)(\s+WHERE\s+)(.*?)$/iu', $rest, $matches);
      if (count($matches) >= 4) {
        $whereHtml = $matches[1] . '<br/>' . $matches[2];
        $rest = $matches[3];
      }

      // try to match & indent ORDER BY/GROUP BY/LIMIT/OFFSET
      $endHtml = '';
      preg_match('/^(.*?)(\s+(?:ORDER\s+BY|GROUP\s+BY|LIMIT|OFFSET)\s+)(.*)$/iu', $rest, $matches);
      if (count($matches) >= 4) {
        $endHtml = $matches[1] . '<br/>' . $matches[2];
        $rest = $matches[3];
      }

      $sql = $selectHtml . '<div></div>' . $fromKeyword . $whereHtml . $endHtml . $rest;
    }

    // highlight keywords
    $keywords = new MySQLKeywords();
    $tokens = preg_split('/(\s+)/', $sql, -1, PREG_SPLIT_DELIM_CAPTURE);
    if ($tokens === false) {
      return $sql;
    }
    $output = '';
    foreach ($tokens as $token) {
      $output .= $keywords->isKeyword($token) ? ('<strong style="color:blue">' . $token . '</strong>') : $token;
    }
    return $output;
  }

  protected function formatArrayData($data) {
    return preg_replace(
      '#^\s{4}#m', '', // remove 1rst "tab" of the JSON result
      substr(
        (string)json_encode($data, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK),
        2, // remove "[\n"
        -2 // remove "\n]"
      )
    );
  }

  protected function transformNumericType($data) {
    $search = [
      '#\b101\b#', // array of int
      '#\b102\b#', // array of string
    ];
    $replace = [
      'integer[]', // array of int
      'string[]', // array of string
    ];
    return preg_replace($search, $replace, $data);
  }

  protected function formatTime($doctrineTime) {
    return number_format($doctrineTime * 1000, 1, '.', ' ');
  }
}
