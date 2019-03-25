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
      'price' => 10
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
   * @param int $price
   * @return $this
   */
  function withPrice($price) {
    return $this->update('price', $price);
  }

  function create() {
    $create_output = $this->tester->cliToArray("wc product create --porcelain --allow-root --user=admin --name=\"{$this->data['name']}\" --sku=\"{$this->data['sku']}\" --type=\"{$this->data['type']}\" --regular_price={$this->data['price']}");
    $product_out = $this->tester->cliToArray("wc product get $create_output[0] --format=json --allow-root --user=admin");
    return json_decode($product_out[0], true);
  }

  private function update($item, $value) {
    $data = $this->data;
    $data[$item] = $value;
    $new = clone $this;
    $new->data = $data;
    return $new;
  }
}
