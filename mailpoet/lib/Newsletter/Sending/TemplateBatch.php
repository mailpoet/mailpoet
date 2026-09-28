<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

/**
 * One email prepared for many recipients: a template shared by the whole batch, where every
 * per-recipient value is a placeholder, plus one substitution map per recipient in sending order.
 * The MailPoet Sending Service builds the final emails by replacing the placeholders.
 *
 * Template, as prepared by Tasks\Newsletter::prepareNewsletterForTemplatedSending():
 *   ['id' => 12, 'subject' => 'Hi {{mp_mss_a1b2_1}}', 'body' => ['html' => '<p>Hi {{mp_mss_a1b2_2}}</p>', 'text' => 'Hi {{mp_mss_a1b2_3}}']]
 * Substitutions of one recipient, keyed by email part so each part can carry its own escaping:
 *   ['subject' => ['{{mp_mss_a1b2_1}}' => 'Tom'], 'html' => ['{{mp_mss_a1b2_2}}' => 'Tom'], 'text' => ['{{mp_mss_a1b2_3}}' => 'Tom']]
 * The n-th substitution map belongs to the n-th recipient of the batch.
 */
class TemplateBatch {
  /** @var array{id?: int|null, subject: string, body: array{html?: string, text?: string}} */
  private array $template;

  /** @var array<int, array{subject: array<string, string>, html: array<string, string>, text: array<string, string>}> */
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
   * @param array{subject: array<string, string>, html: array<string, string>, text: array<string, string>} $substitutions
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
   * @return array<int, array{subject: array<string, string>, html: array<string, string>, text: array<string, string>}>
   */
  public function getSubstitutions(): array {
    return $this->substitutions;
  }
}
