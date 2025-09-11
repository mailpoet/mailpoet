<?php declare(strict_types = 1);

namespace MailPoet\Test\WooCommerce\TransactionalEmails;

use MailPoet\WooCommerce\TransactionalEmails\FontFamilyValidator;

class FontFamilyValidatorTest extends \MailPoetUnitTest {

  /** @var FontFamilyValidator */
  private $validator;

  public function _before() {
    parent::_before();
    $this->validator = new FontFamilyValidator();
  }

  public function testValidateFontFamilyWithValidFont() {
    $result = $this->validator->validateFontFamily('Arial');
    $this->assertEquals('Arial', $result);
  }

  public function testValidateFontFamilyWithValidFontList() {
    $result = $this->validator->validateFontFamily('Arial, sans-serif');
    $this->assertEquals('Arial, sans-serif', $result);
  }

  public function testValidateFontFamilyWithCaseInsensitiveFont() {
    $result = $this->validator->validateFontFamily('arial');
    $this->assertEquals('arial', $result);
  }

  public function testValidateFontFamilyWithQuotedFont() {
    $result = $this->validator->validateFontFamily('"Times New Roman"');
    $this->assertEquals('Times New Roman', $result);
  }

  public function testValidateFontFamilyWithEmptyValue() {
    $result = $this->validator->validateFontFamily('');
    $this->assertEquals(FontFamilyValidator::DEFAULT_FONT_FAMILY, $result);
  }

  public function testValidateFontFamilyWithNullValue() {
    $result = $this->validator->validateFontFamily(null);
    $this->assertEquals(FontFamilyValidator::DEFAULT_FONT_FAMILY, $result);
  }

  public function testValidateFontFamilyWithCustomFont() {
    $result = $this->validator->validateFontFamily('MyCustomFont123');
    $this->assertEquals('MyCustomFont123', $result);
  }

  public function testValidateFontFamilyHandlesMaliciousInput() {
    $maliciousInputs = [
      'Arial" onload="alert(1)" x="' => 'Arial onloadalert1 x',
      'Times" onclick="alert(\'XSS\')" data="' => 'Times onclickalertXSS data',
      'Arial; background: url(javascript:alert(1));' => 'Arial background urljavascriptalert1',
      'Arial</style><script>alert(1)</script><style>' => 'Arialstylescriptalert1scriptstyle',
      'Times"</style><script>alert(1)</script><style a="' => 'Timesstylescriptalert1scriptstyle a',
    ];

    foreach ($maliciousInputs as $input => $expected) {
      $result = $this->validator->validateFontFamily($input);

      // Assert the sanitized output matches the expected result
      $this->assertEquals($expected, $result);

      // Verify dangerous characters are removed
      $this->assertStringNotContainsString('"', $result);
      $this->assertStringNotContainsString('<', $result);
      $this->assertStringNotContainsString('>', $result);
      $this->assertStringNotContainsString(';', $result);
      $this->assertStringNotContainsString('\\', $result);

      // Result should still be a non-empty string
      $this->assertNotEmpty($result);
    }
  }

  public function testSanitizationRemovesDangerousCharacters() {
    $dangerousInputs = [
      'Arial<script>alert(1)</script>',
      'Times"onclick="alert(1)"',
      'Verdana;background:red;',
      'Georgia{color:red}',
      'Helvetica>body{color:red}',
      'Arial[onclick=alert(1)]',
      'Times(javascript:alert(1))',
      'Comic Sans MS=evil',
      'Arial+Times',
      'Verdana*{color:red}',
      'Georgia^[onclick=alert]',
      'Times|onclick|',
      'Arial\\onclick\\',
      'Helvetica/onclick/',
      'Arial@import',
      'Times#id',
      'Verdana$var',
      'Georgia%3Cscript%3E',
      'Arial&lt;script&gt;',
      'Times~hover',
      'Arial!important',
      'Verdana?query',
    ];

    foreach ($dangerousInputs as $input) {
      $result = $this->validator->validateFontFamily($input);
      // Should either be sanitized to safe version or default font
      $this->assertNotEquals($input, $result, "Dangerous input should be sanitized: $input");
      // Should not contain dangerous characters in the result
      $this->assertStringNotContainsString('<', $result);
      $this->assertStringNotContainsString('>', $result);
      $this->assertStringNotContainsString(';', $result);
      $this->assertStringNotContainsString('\\', $result);
      $this->assertStringNotContainsString('[', $result);
      $this->assertStringNotContainsString(']', $result);
    }
  }

  /**
   * Test font family lists with custom fonts
   */
  public function testValidateFontFamilyWithCustomFontLists() {
    // Should accept custom fonts in lists
    $result = $this->validator->validateFontFamily('Arial, MyCustomFont123');
    $this->assertEquals('Arial, MyCustomFont123', $result);

    $result = $this->validator->validateFontFamily('CustomFont, Helvetica');
    $this->assertEquals('CustomFont, Helvetica', $result);

    $result = $this->validator->validateFontFamily('Arial, Helvetica, CustomWebFont');
    $this->assertEquals('Arial, Helvetica, CustomWebFont', $result);
  }

  /**
   * Test performance with long input strings
   */
  public function testValidateFontFamilyWithLongInput() {
    $longInput = str_repeat('A', 1000) . '" onclick="alert(1)" x="';
    $result = $this->validator->validateFontFamily($longInput);
    // Should sanitize and still return a font name (not default)
    $this->assertStringNotContainsString('"', $result);
    $this->assertStringNotContainsString('=', $result);
    $this->assertStringNotContainsString('(', $result);
    $this->assertStringNotContainsString(')', $result);
    $this->assertNotEmpty($result);
    $this->assertNotEquals(FontFamilyValidator::DEFAULT_FONT_FAMILY, $result);
  }

  /**
   * Test edge cases with whitespace
   */
  public function testValidateFontFamilyHandlesWhitespace() {
    $result = $this->validator->validateFontFamily('  Arial  ');
    $this->assertEquals('Arial', $result);

    $result = $this->validator->validateFontFamily('Arial  ,  Helvetica');
    $this->assertEquals('Arial, Helvetica', $result);

    $result = $this->validator->validateFontFamily("Arial\n,\tHelvetica");
    $this->assertEquals('Arial, Helvetica', $result);
  }
}
