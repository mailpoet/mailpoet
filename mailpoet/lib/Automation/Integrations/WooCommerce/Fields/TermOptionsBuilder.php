<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Fields;

use MailPoet\Automation\Engine\WordPress;
use WP_Error;
use WP_Term;

class TermOptionsBuilder {
  /** @var WordPress */
  private $wordPress;

  public function __construct(
    WordPress $wordPress
  ) {
    $this->wordPress = $wordPress;
  }

  /** @return array<array{id: int, name: string}> */
  public function getCategoryOptions(): array {
    return $this->getTermOptions('product_cat');
  }

  /** @return array<array{id: int, name: string}> */
  public function getTagOptions(): array {
    return $this->getTermOptions('product_tag');
  }

  /** @return array<array{id: int, name: string}> */
  private function getTermOptions(string $taxonomy): array {
    $terms = $this->wordPress->getTerms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
    if ($terms instanceof WP_Error) {
      return [];
    }
    return $this->buildTermsList((array)$terms);
  }

  /** @return array<array{id: int, name: string}> */
  private function buildTermsList(array $terms, int $parentId = 0): array {
    $parents = array_filter($terms, function ($term) use ($parentId) {
      return $term instanceof WP_Term && $term->parent === $parentId;
    });

    usort($parents, function (WP_Term $a, WP_Term $b) {
      return $a->name <=> $b->name;
    });

    $list = [];
    foreach ($parents as $term) {
      $id = $term->term_id; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $list[] = ['id' => (int)$id, 'name' => $term->name];
      foreach ($this->buildTermsList($terms, $id) as $child) {
        $list[] = ['id' => (int)$child['id'], 'name' => "$term->name | {$child['name']}"];
      }
    }
    return $list;
  }
}
