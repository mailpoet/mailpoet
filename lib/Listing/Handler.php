<?php
namespace MailPoet\Listing;

if (!defined('ABSPATH')) exit;

class Handler {
  const DEFAULT_LIMIT_PER_PAGE = 20;

  function getSelection($model_class, array $data) {
    $data = $this->processData($data);
    $table_name = $model_class::$_table;
    $model = \Model::factory($model_class);

    if (method_exists($model_class, 'listingQuery')) {
      $custom_query = call_user_func_array(
        [$model_class, 'listingQuery'],
        [$data]
      );
      if (!empty($data['selection'])) {
        $custom_query->whereIn($table_name . '.id', $data['selection']);
      }
      return $custom_query;
    } else {
      $model = $this->setFilter($model, $data);
      $this->setGroup($model, $data);
      $this->setSearch($model, $data);

      if (!empty($data['selection'])) {
        $model->whereIn($table_name . '.id', $data['selection']);
      }
      return $model;
    }
  }

  function get($model_class, array $data) {
    $data = $this->processData($data);
    $table_name = $model_class::$_table;
    $model = \Model::factory($model_class);
    // get groups
    $groups = [];
    if (method_exists($model_class, 'groups')) {
      $groups = call_user_func_array(
        [$model_class, 'groups'],
        [$data]
      );
    }

    // get filters
    $filters = [];
    if (method_exists($model_class, 'filters')) {
      $filters = call_user_func_array(
        [$model_class, 'filters'],
        [$data]
      );
    }

    // get items and total count
    if (method_exists($model_class, 'listingQuery')) {
      $custom_query = call_user_func_array(
        [$model_class, 'listingQuery'],
        [$data]
      );

      $count = $custom_query->count();

      $items = $custom_query
        ->offset($data['offset'])
        ->limit($data['limit'])
        ->{'order_by_' . $data['sort_order']}(
          $table_name . '.' . $data['sort_by']
        )
        ->findMany();
    } else {
      $model = $this->setFilter($model, $data);
      $this->setGroup($model, $data);
      $this->setSearch($model, $data);
      $this->setOrder($model, $data, $table_name);

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

  private function setSearch(\ORMWrapper $model, array $data) {
    if (empty($data['search'])) {
      return;
    }
    return $model->filter('search', $data['search']);
  }

  private function setOrder(\ORMWrapper $model, array $data, $table_name) {
    return $model
      ->{'order_by_' . $data['sort_order']}(
        $table_name . '.' . $data['sort_by']);
  }

  private function setGroup(\ORMWrapper $model, array $data) {
    if ($data['group'] === null) {
      return;
    }
    $model->filter('groupBy', $data['group']);
  }

  private function setFilter(\ORMWrapper $model, array $data) {
    if ($data['filter'] === null) {
      return $model;
    }
    return $model->filter('filterBy', $data['filter']);
  }

  private function processData(array $data) {
    // check if sort order was specified or default to "asc"
    $sort_order = (!empty($data['sort_order'])) ? $data['sort_order'] : 'asc';
    // constrain sort order value to either be "asc" or "desc"
    $sort_order = ($sort_order === 'asc') ? 'asc' : 'desc';

    // sanitize sort by
    $sort_by = (!empty($data['sort_by']))
      ? filter_var($data['sort_by'], FILTER_SANITIZE_STRING)
      : '';

    if (empty($sort_by)) {
      $sort_by = 'id';
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
      'sort_by' => $sort_by,
      'sort_order' => $sort_order,
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
