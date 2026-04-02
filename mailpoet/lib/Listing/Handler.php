<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Listing;

class Handler {
  public function getListingDefinition(array $data): ListingDefinition {
    $data = $this->processData($data);
    return new ListingDefinition(
      $data['group'],
      $data['filter'] ?? [],
      $data['search'],
      $data['params'] ?? [],
      $data['sort_by'],
      $data['sort_order'],
      $data['offset'],
      $data['limit'],
      $data['selection'] ?? []
    );
  }

  private function sanitizeSortBy(string $sortBy): string {
    $sortBy = trim($sortBy);
    if ($sortBy === '') {
      return 'id';
    }

    // Fallback to `id` when there is at least one non-identifier character.
    if (strspn($sortBy, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_') !== strlen($sortBy)) {
      return 'id';
    }

    return $sortBy;
  }

  private function processData(array $data) {
    // check if sort order was specified or default to "asc"
    $sortOrder = (!empty($data['sort_order'])) ? $data['sort_order'] : 'asc';
    // constrain sort order value to either be "asc" or "desc"
    $sortOrder = ($sortOrder === 'asc') ? 'asc' : 'desc';

    // sanitize sort by
    $sortBy = (!empty($data['sort_by']))
      ? $this->sanitizeSortBy((string)$data['sort_by'])
      : '';

    if (empty($sortBy)) {
      $sortBy = 'id';
    }

    $data = [
      // extra parameters
      'params' => (isset($data['params']) ? $data['params'] : []),
      // pagination
      'offset' => (isset($data['offset']) ? (int)$data['offset'] : 0),
      'limit' => (isset($data['limit'])
        ? (int)$data['limit']
        : PageLimit::DEFAULT_LIMIT_PER_PAGE
      ),
      // searching
      'search' => (isset($data['search']) ? $data['search'] : null),
      // sorting
      'sort_by' => $sortBy,
      'sort_order' => $sortOrder,
      // grouping
      'group' => (isset($data['group']) ? $data['group'] : null),
      // filters
      'filter' => (isset($data['filter']) ? $data['filter'] : null),
      // selection
      'selection' => (isset($data['selection']) ? $data['selection'] : null),
    ];

    return $data;
  }
}
