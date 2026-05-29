<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use MailPoet\Logging\LoggerFactory;

/**
 * Makes an in-memory WooCommerce data object (order, customer, product, ...)
 * safe to use as email or automation preview sample data.
 *
 * Preview helpers assign the object a placeholder ID (e.g. 12345) so templates
 * render realistic content. Without this trait two things go wrong if a hook or
 * integration touches the object while the preview renders:
 *
 *  - save() / save_meta_data() / delete() treat the placeholder ID as an
 *    existing record and overwrite or remove the real order/customer 12345.
 *  - read_meta_data() lazy-loads the real record's meta from the database,
 *    leaking it into the preview.
 *
 * Mixing this trait into a thin subclass of the WooCommerce type turns those
 * operations into safe no-ops.
 */
trait NonPersistablePreviewData {
  // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- declares the WC_Data::get_id() dependency.
  abstract public function get_id();

  /**
   * @return int|string The placeholder ID; nothing is written to the database.
   */
  public function save() {
    $this->logPreviewPersistAttempt('save');
    return $this->get_id();
  }

  public function save_meta_data() { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- overrides WC_Data::save_meta_data().
    $this->logPreviewPersistAttempt('save_meta_data');
  }

  /**
   * @param bool $forceDelete
   * @return bool Always false; preview data is never removed from the database.
   */
  public function delete($forceDelete = false) {
    $this->logPreviewPersistAttempt('delete');
    return false;
  }

  /**
   * Prevents lazy-loading the real record's meta for the placeholder ID.
   *
   * @param bool $forceRead
   */
  public function read_meta_data($forceRead = false) { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- overrides WC_Data::read_meta_data().
    if (!is_array($this->meta_data)) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $this->meta_data = []; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }
  }

  private function logPreviewPersistAttempt(string $method): void {
    try {
      LoggerFactory::getInstance()->getLogger(LoggerFactory::TOPIC_EMAIL_EDITOR)->error(
        sprintf(
          'Blocked %s() on preview-only WooCommerce data (%s, ID %s). An integration tried to persist email-preview sample data.',
          $method,
          static::class,
          (string)$this->get_id()
        )
      );
    } catch (\Throwable $e) {
      // Ignore: logging failures must not affect preview rendering.
    }
  }
}
