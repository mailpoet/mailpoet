<?php

namespace MailPoet\Test\DataFactories;

class WooCommerceCustomer {

  /** @var \AcceptanceTester */
  private $tester;

  /** @var array */
  private $data;

  function __construct(\AcceptanceTester $tester) {
    $unique_id = uniqid();
    $this->tester = $tester;
    $this->data = [
      'first_name' => "FirstName_$unique_id",
      'last_name' => "LastName_$unique_id",
      'email' => "woo_customer_$unique_id@example.com",
      'password' => "woo_customer_$unique_id",
    ];
  }

  /**
 * @param string $name
 * @return $this
 */
  function withFirstName($name) {
    return $this->update('first_name', $name);
  }

  /**
   * @param string $name
   * @return $this
   */
  function withLastName($name) {
    return $this->update('last_name', $name);
  }

  /**
   * @param string $password
   * @return $this
   */
  function withPassword($password) {
    return $this->update('password', $password);
  }

  /**
   * @param string $email
   * @return $this
   */
  function withEmail($email) {
    return $this->update('email', $email);
  }


  function create() {
    $create_output = $this->tester->cliToArray("wc customer create --porcelain --allow-root --user=admin --first_name=\"{$this->data['first_name']}\" --last_name=\"{$this->data['last_name']}\" --email=\"{$this->data['email']}\" --password=\"{$this->data['password']}\"");
    $customer_out = $this->tester->cliToArray("wc customer get $create_output[0] --format=json --allow-root --user=admin");
    return json_decode($customer_out[0], true);
  }

  private function update($item, $value) {
    $data = $this->data;
    $data[$item] = $value;
    $new = clone $this;
    $new->data = $data;
    return $new;
  }
}
