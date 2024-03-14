<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Fields;

use MailPoet\Automation\Engine\WordPress;
use WP_Error;
use WP_Term;

class TermOptionsBuilder {
  /** @var WordPress */
  private $wordPress;

  /** @var array<string, array<array{id: int, name: string}>> */
  private $terms = [];

  public function __construct(
    WordPress $wordPress
  ) {
    $this->wordPress = $wordPress;
  }

  /** @return array<array{id: int, name: string}> */
  public function getTermOptions(string $taxonomy): array {
    if (isset($this->terms[$taxonomy])) {
      return $this->terms[$taxonomy];
    }
    $terms = $this->wordPress->getTerms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'orderby' => 'name']);
    if ($terms instanceof WP_Error) {
      $this->terms[$taxonomy] = [];
      return $this->terms[$taxonomy];
    }
    $this->terms[$taxonomy] = $this->buildTermsList((array)$terms);
    return $this->terms[$taxonomy];
  }

  /** @return array<array{id: int, name: string}> */
  private function buildTermsList(array $terms, int $parentId = 0, array $lookup = []): array {
    if ($lookup === []) {
      foreach ($terms as $term) {
        if (!$term instanceof WP_Term) {
          continue;
        }
        $lookup[$term->parent][] = $term;
      }
    }
    if (empty($lookup[$parentId])) {
      return [];
    }

    $list = [];
    foreach ($lookup[$parentId] as $term) {
      if (!$term instanceof WP_Term || $term->parent !== $parentId) {
        continue;
      }
      $id = $term->term_id; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $list[] = ['id' => (int)$id, 'name' => $term->name];
      if (empty($lookup[$id])) {
        continue;
      }
      foreach ($this->buildTermsList($lookup[$id], $id, $lookup) as $child) {
        $list[] = ['id' => (int)$child['id'], 'name' => "$term->name | {$child['name']}"];
      }
    }
    return $list;
  }
}
