<?php declare(strict_types = 1);

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
  const TYPE_SUBSCRIPTION = 'subscription';
  const TYPE_VARIABLE_SUBSCRIPTION = 'variable-subscription';

  public function __construct(
    \AcceptanceTester $tester
  ) {
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
  public function withName($name) {
    return $this->update('name', $name);
  }

  /**
   * @param string $description
   * @return $this
   */
  public function withDescription($description) {
    return $this->update('description', $description);
  }

  /**
   * @param string $shortDescription
   * @return $this
   */
  public function withShortDescription($shortDescription) {
    return $this->update('shortDescription', $shortDescription);
  }

  /**
   * @param string $type
   * @return $this
   */
  public function withType($type) {
    return $this->update('type', $type);
  }

  /**
   * @param string $sku
   * @return $this
   */
  public function withSku($sku) {
    return $this->update('sku', $sku);
  }

  /**
   * @param int $price
   * @return $this
   */
  public function withPrice($price) {
    return $this->update('price', $price);
  }

  /**
   * @param array $ids
   * @return $this
   */
  public function withCategoryIds($ids) {
    $ids = array_map(function($id){
      return ['id' => $id];
    }, $ids);
    return $this->update('categoryIds', $ids);
  }

  /**
   * @param array $ids
   * @return $this
   */
  public function withTagIds($ids) {
    $ids = array_map(function($id){
      return ['id' => $id];
    }, $ids);
    return $this->update('tagIds', $ids);
  }

  /**
   * @param array $images
   * @return $this
   */
  public function withImages($images) {
    $images = array_map(function($src){
      return ['src' => $src];
    }, $images);
    return $this->update('images', $images);
  }

  public function create() {
    $createCommand = ['wc', 'product', 'create', '--porcelain', '--user=admin'];
    $createCommand[] = "--name='{$this->data['name']}'";
    $createCommand[] = "--type={$this->data['type']}";
    $createCommand[] = "--regular_price={$this->data['price']}";
    if ($this->data['description']) {
      $createCommand[] = "--description='{$this->data['description']}'";
    }
    if ($this->data['shortDescription']) {
      $createCommand[] = "--short_description='{$this->data['shortDescription']}'";
    }
    if ($this->data['sku']) {
      $createCommand[] = "--sku='{$this->data['sku']}'";
    } else {
      $create_command[] = '--sku=WC_PR_' . bin2hex(random_bytes(7)); // phpcs:ignore
    }
    if ($this->data['categoryIds']) {
      $createCommand[] = '--categories=\'' . json_encode($this->data['categoryIds']) . '\'';
    }
    if ($this->data['tagIds']) {
      $createCommand[] = '--tags=\'' . json_encode($this->data['tagIds']) . '\'';
    }
    if ($this->data['images']) {
      $createCommand[] = '--images=\'' . json_encode($this->data['images']) . '\'';
    }
    $createOutput = $this->tester->cliToString($createCommand);
    $productOut = $this->tester->cliToString(['wc', 'product', 'get', $createOutput, '--format=json', '--user=admin']);
    return json_decode($productOut, true);
  }

  public function createCategory($name) {
    $createOutput = $this->tester->cliToString(['wc', 'product_cat', 'create', '--porcelain', '--user=admin', "--name='$name'"]);
    return $createOutput;
  }

  public function createTag($name) {
    $createOutput = $this->tester->cliToString(['wc', 'product_tag', 'create', '--porcelain', '--user=admin', "--name='$name'"]);
    return $createOutput;
  }

  /**
   * @param int $id
   */
  public function delete($id) {
    $this->tester->cliToArray(['wc', 'product', 'delete', $id, '--force=1', '--user=admin']);
  }

  public function deleteAll() {
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
