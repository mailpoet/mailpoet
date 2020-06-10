<?php

namespace MailPoet\Listing;

use MailPoetVendor\Paris\Model;
use MailPoetVendor\Paris\ORMWrapper;

class Handler {
  const DEFAULT_LIMIT_PER_PAGE = 20;

  public function getSelection($modelClass, array $data) {
    $data = $this->processData($data);
    $tableName = $modelClass::$_table;
    $model = Model::factory($modelClass);

    if (method_exists($modelClass, 'listingQuery')) {
      $customQuery = call_user_func_array(
        [$modelClass, 'listingQuery'],
        [$data]
      );
      if (!empty($data['selection'])) {
        $customQuery->whereIn($tableName . '.id', $data['selection']);
      }
      return $customQuery;
    } else {
      $model = $this->setFilter($model, $data);
      $this->setGroup($model, $data);
      $this->setSearch($model, $data);

      if (!empty($data['selection'])) {
        $model->whereIn($tableName . '.id', $data['selection']);
      }
      return $model;
    }
  }

  public function get($modelClass, array $data) {
    $data = $this->processData($data);
    $tableName = $modelClass::$_table;
    $model = Model::factory($modelClass);
    // get groups
    $groups = [];
    if (method_exists($modelClass, 'groups')) {
      $groups = call_user_func_array(
        [$modelClass, 'groups'],
        [$data]
      );
    }

    // get filters
    $filters = [];
    if (method_exists($modelClass, 'filters')) {
      $filters = call_user_func_array(
        [$modelClass, 'filters'],
        [$data]
      );
    }

    // get items and total count
    if (method_exists($modelClass, 'listingQuery')) {
      $customQuery = call_user_func_array(
        [$modelClass, 'listingQuery'],
        [$data]
      );

      $count = $customQuery->count();

      $items = $customQuery
        ->offset($data['offset'])
        ->limit($data['limit'])
        ->{'order_by_' . $data['sort_order']}(
          $tableName . '.' . $data['sort_by']
        )
        ->findMany();
    } else {
      $model = $this->setFilter($model, $data);
      $this->setGroup($model, $data);
      $this->setSearch($model, $data);
      $this->setOrder($model, $data, $tableName);

      $count = $model->count();

      $items = $model
        ->offset($data['offset'])
        ->limit($data['limit'])
        ->findMany();
    }

    return [
      'count' => $count,
      'filters' => $filters,
      'groups' => $groups,
      'items' => $items,
    ];
  }

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

  private function setSearch(ORMWrapper $model, array $data) {
    if (empty($data['search'])) {
      return;
    }
    return $model->filter('search', $data['search']);
  }

  private function setOrder(ORMWrapper $model, array $data, $tableName) {
    return $model
      ->{'order_by_' . $data['sort_order']}(
        $tableName . '.' . $data['sort_by']);
  }

  private function setGroup(ORMWrapper $model, array $data) {
    if ($data['group'] === null) {
      return;
    }
    $model->filter('groupBy', $data['group']);
  }

  private function setFilter(ORMWrapper $model, array $data) {
    if ($data['filter'] === null) {
      return $model;
    }
    return $model->filter('filterBy', $data['filter']);
  }

  private function processData(array $data) {
    // check if sort order was specified or default to "asc"
    $sortOrder = (!empty($data['sort_order'])) ? $data['sort_order'] : 'asc';
    // constrain sort order value to either be "asc" or "desc"
    $sortOrder = ($sortOrder === 'asc') ? 'asc' : 'desc';

    // sanitize sort by
    $sortBy = (!empty($data['sort_by']))
      ? filter_var($data['sort_by'], FILTER_SANITIZE_STRING)
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
