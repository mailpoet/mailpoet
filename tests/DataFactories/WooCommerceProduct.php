<?php

namespace MailPoet\Test\DataFactories;

class WooCommerceProduct {

  /** @var \AcceptanceTester */
  private $tester;

  /** @var array */
  private $data;

  const TYPE_SIMPLE = 'simple';
  const TYPE_VIRTUAL = 'virtual';
  const TYPE_DOWNLOADABLE = 'downloadable';
  const TYPE_EXTERNAL = 'external';
  const TYPE_VARIABLE = 'variable';

  function __construct(\AcceptanceTester $tester) {
    $this->tester = $tester;
    $this->data = [
      'name' => 'Product',
      'description' => null,
      'shortDescription' => null,
      'type' => self::TYPE_SIMPLE,
      'sku' => null,
      'price' => 10,
      'categoryIds' => null,
      'tagIds' => null,
      'images' => null,
    ];
  }

  /**
   * @param string $name
   * @return $this
   */
  function withName($name) {
    return $this->update('name', $name);
  }

  /**
   * @param string $description
   * @return $this
   */
  function withDescription($description) {
    return $this->update('description', $description);
  }

  /**
   * @param string $shortDescription
   * @return $this
   */
  function withShortDescription($shortDescription) {
    return $this->update('shortDescription', $shortDescription);
  }

  /**
   * @param string $type
   * @return $this
   */
  function withType($type) {
    return $this->update('type', $type);
  }

  /**
   * @param string $sku
   * @return $this
   */
  function withSku($sku) {
    return $this->update('sku', $sku);
  }

  /**
   * @param int $price
   * @return $this
   */
  function withPrice($price) {
    return $this->update('price', $price);
  }

  /**
   * @param array $ids
   * @return $this
   */
  function withCategoryIds($ids) {
    $ids = array_map(function($id){
      return ['id' => $id];
    }, $ids);
    return $this->update('categoryIds', $ids);
  }

  /**
   * @param array $ids
   * @return $this
   */
  function withTagIds($ids) {
    $ids = array_map(function($id){
      return ['id' => $id];
    }, $ids);
    return $this->update('tagIds', $ids);
  }

  /**
   * @param array $images
   * @return $this
   */
  function withImages($images) {
    $images = array_map(function($src){
      return ['src' => $src];
    }, $images);
    return $this->update('images', $images);
  }

  function create() {
    $create_command = ['wc', 'product', 'create', '--porcelain', '--user=admin'];
    $create_command[] = "--name={$this->data['name']}";
    $create_command[] = "--type={$this->data['type']}";
    $create_command[] = "--regular_price={$this->data['price']}";
    if ($this->data['description']) {
      $create_command[] = "--description={$this->data['description']}";
    }
    if ($this->data['shortDescription']) {
      $create_command[] = "--short_description={$this->data['shortDescription']}";
    }
    if ($this->data['sku']) {
      $create_command[] = "--sku={$this->data['sku']}";
    } else {
      $create_command[] = '--sku=WC_PR_' . bin2hex(random_bytes(7)); // phpcs:ignore
    }
    if ($this->data['categoryIds']) {
      $create_command[] = '--categories=' . json_encode($this->data['categoryIds']);
    }
    if ($this->data['tagIds']) {
      $create_command[] = '--tags=' . json_encode($this->data['tagIds']);
    }
    if ($this->data['images']) {
      $create_command[] = '--images=' . json_encode($this->data['images']);
    }
    $create_output = $this->tester->cliToArray($create_command);
    $product_out = $this->tester->cliToArray(['wc', 'product', 'get', $create_output[0], '--format=json', '--user=admin']);
    return json_decode($product_out[0], true);
  }

  function createCategory($name) {
    $create_output = $this->tester->cliToArray(['wc', 'product_cat', 'create', '--porcelain', '--user=admin', "--name=$name"]);
    return $create_output[0];
  }

  function createTag($name) {
    $create_output = $this->tester->cliToArray(['wc', 'product_tag', 'create', '--porcelain', '--user=admin', "--name=$name"]);
    return $create_output[0];
  }

  /**
   * @param int $id
   */
  function delete($id) {
    $this->tester->cliToArray(['wc', 'product', 'delete', $id, '--force=1', '--user=admin']);
  }

  function deleteAll() {
    $list = $this->tester->cliToArray(['wc', 'product', 'list', '--format=json', '--user=admin', '--fields=id']);
    foreach (json_decode($list[0], true) as $item) {
      $this->delete($item['id']);
    }
  }

  private function update($item, $value) {
    $data = $this->data;
    $data[$item] = $value;
    $new = clone $this;
    $new->data = $data;
    return $new;
  }
}
