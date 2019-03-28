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
      'type' => self::TYPE_SIMPLE,
      'sku' => 'WC_PR_'. uniqid(),
      'price' => 10,
      'categoryId' => null,
      'tagId' => null
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
   * @return $this
   */
  function withRandomSku() {
    return $this->update('sku', 'WC_PR_'. uniqid());
  }

  /**
   * @param int $price
   * @return $this
   */
  function withPrice($price) {
    return $this->update('price', $price);
  }

  /**
   * @param int $id
   * @return $this
   */
  function withCategory($id) {
    return $this->update('categoryId', $id);
  }

  /**
   * @param int $id
   * @return $this
   */
  function withTag($id) {
    return $this->update('tagId', $id);
  }

  function create() {
    $create_command = "wc product create --porcelain --allow-root --user=admin";
    $create_command .= " --name=\"{$this->data['name']}\"";
    $create_command .= " --sku=\"{$this->data['sku']}\"";
    $create_command .= " --type=\"{$this->data['type']}\"";
    $create_command .= " --regular_price={$this->data['price']}";
    if ($this->data['categoryId']) {
      $create_command .= " --categories='[{ \"id\": {$this->data['categoryId']} }]'";
    }
    if ($this->data['tagId']) {
      $create_command .= " --tags='[{ \"id\": {$this->data['tagId']} }]'";
    }
    $create_output = $this->tester->cliToArray($create_command);
    $product_out = $this->tester->cliToArray("wc product get $create_output[0] --format=json --allow-root --user=admin");
    return json_decode($product_out[0], true);
  }

  function createCategory($name) {
    $create_output = $this->tester->cliToArray("wc product_cat create --porcelain --allow-root --user=admin --name=\"{$name}\"");
    return $create_output[0];
  }

  function createTag($name) {
    $create_output = $this->tester->cliToArray("wc product_tag create --porcelain --allow-root --user=admin --name=\"{$name}\"");
    return $create_output[0];
  }

  /**
   * @param int $id
   */
  function delete($id) {
    $this->tester->cliToArray("wc product delete $id --force=1 --allow-root --user=admin");
  }

  function deleteAll() {
    $list = $this->tester->cliToArray("wc product list --format=json --allow-root --user=admin --fields=id");
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
