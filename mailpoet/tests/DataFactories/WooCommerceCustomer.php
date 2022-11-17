<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

class WooCommerceCustomer {

  /** @var \AcceptanceTester */
  private $tester;

  /** @var array */
  private $data;

  public function __construct(
    \AcceptanceTester $tester
  ) {
    $uniqueId = bin2hex(random_bytes(7)); // phpcs:ignore
    $this->tester = $tester;
    $this->data = [
      'first_name' => "FirstName_$uniqueId",
      'last_name' => "LastName_$uniqueId",
      'email' => "woo_customer_$uniqueId@example.com",
      'password' => "woo_customer_$uniqueId",
    ];
  }

  /**
 * @param string $name
 * @return $this
 */
  public function withFirstName($name) {
    return $this->update('first_name', $name);
  }

  /**
   * @param string $name
   * @return $this
   */
  public function withLastName($name) {
    return $this->update('last_name', $name);
  }

  /**
   * @param string $password
   * @return $this
   */
  public function withPassword($password) {
    return $this->update('password', $password);
  }

  /**
   * @param string $email
   * @return $this
   */
  public function withEmail($email) {
    return $this->update('email', $email);
  }

  public function create() {
    $createOutput = $this->tester->cliToArray(['wc', 'customer', 'create', '--porcelain', '--user=admin', "--first_name={$this->data['first_name']}", "--last_name={$this->data['last_name']}", "--email={$this->data['email']}", "--password={$this->data['password']}"]);
    $customerOut = $this->tester->cliToString(['wc', 'customer', 'get', $createOutput[0], '--format=json', '--user=admin']);
    return json_decode($customerOut, true);
  }

  /**
   * @param int $id
   */
  public function delete($id) {
    $this->tester->cliToArray(['wc', 'customer', 'delete', $id, '--force=1', '--user=admin']);
  }

  public function deleteAll() {
    $list = $this->tester->cliToArray(['wc', 'customer', 'list', '--format=json', '--user=admin', '--fields=id']);
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
