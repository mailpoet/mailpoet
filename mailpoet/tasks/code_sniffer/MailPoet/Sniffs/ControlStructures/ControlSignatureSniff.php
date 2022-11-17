<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Sniffs;

/**
 * Verifies spacing of control statements.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

if (class_exists('PHP_CodeSniffer\Sniffs\AbstractPatternSniff', true) === false) {
  throw new \PHP_CodeSniffer\Exceptions\RuntimeException('Class PHP_CodeSniffer\Sniffs\AbstractPatternSniff not found');
}

/**
 * Verifies spacing of control statements.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class ControlSignatureSniff extends \PHP_CodeSniffer\Sniffs\AbstractPatternSniff {

  /**
   * If true, comments will be ignored if they are found in the code.
   *
   * @var bool
   */
  public $ignoreComments = true;

  /**
   * Returns the patterns that this test wishes to verify.
   *
   * @return string[]
   */
  protected function getPatterns() {
    return [
      'do {EOL...} while (...);EOL',
      'while (...) {EOL',
      'for (...) {EOL',
      'if (...) {EOL',
      'foreach (...) {EOL',
      '} else if (...) {EOL',
      '} elseif (...) {EOL',
      '} else {EOL',
      'do {EOL',
      'switch (...) {EOL',
      'catch (...) {EOL',
    ];
  }
}
