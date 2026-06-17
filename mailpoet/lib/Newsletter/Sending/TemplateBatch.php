<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

class TemplateBatch {
  /** @var array{id?: int|null, subject: string, body: array{html?: string, text?: string}} */
  private array $template;

  /** @var array<int, array<string, string>> */
  private array $substitutions = [];

  /**
   * @param array{id?: int|null, subject: string, body: array{html?: string, text?: string}} $template
   */
  public function __construct(
    array $template
  ) {
    $this->template = $template;
  }

  /**
   * @param array<string, string> $substitutions
   */
  public function addSubstitutions(array $substitutions): void {
    $this->substitutions[] = $substitutions;
  }

  /**
   * @return array{id?: int|null, subject: string, body: array{html?: string, text?: string}}
   */
  public function getTemplate(): array {
    return $this->template;
  }

  /**
   * @return array<int, array<string, string>>
   */
  public function getSubstitutions(): array {
    return $this->substitutions;
  }
}
