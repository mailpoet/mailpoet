<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use MailPoet\Test\DataFactories\Newsletter;

class EditorLineHeightCest {
  public function changeLineHeight(\AcceptanceTester $i) {
    $textSize = '10';
    $h1Size = '40';
    $h2Size = '30';
    $h3Size = '20';
    $textLineHeight = '1.2';
    $headingLineHeight = '2.0';

    $i->wantTo('Edit line height settings in a newsletter');
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithText.json')
      ->create();

    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->click('.mailpoet_styles_region');

    // set text sizes
    $i->selectOption('text-size', $textSize . 'px');
    $i->selectOption('h1-size', $h1Size . 'px');
    $i->selectOption('h2-size', $h2Size . 'px');
    $i->selectOption('h3-size', $h3Size . 'px');

    // check & set line heights
    $this->checkLineHeightOptions($i, '#mailpoet_text_line_height');
    $this->checkLineHeightOptions($i, '#mailpoet_heading_line_height');
    $i->selectOption('#mailpoet_text_line_height', $textLineHeight);
    $i->selectOption('#mailpoet_heading_line_height', $headingLineHeight);

    // check Editor
    $this->checkLineHeightInEditor($i, $textSize, $textLineHeight, 'p');
    $this->checkLineHeightInEditor($i, $h1Size, $headingLineHeight, 'h1');
    $this->checkLineHeightInEditor($i, $h2Size, $headingLineHeight, 'h2');
    $this->checkLineHeightInEditor($i, $h3Size, $headingLineHeight, 'h3');

    // check rendered Preview
    $i->click('.mailpoet_show_preview');
    $i->waitForElement('#mailpoet_browser_preview_iframe');
    $i->switchToIframe('#mailpoet_browser_preview_iframe');
    $this->checkLineHeightInPreview($i, $textSize, $textLineHeight, 'p');
    $this->checkLineHeightInPreview($i, $h1Size, $headingLineHeight, 'h1');
    $this->checkLineHeightInPreview($i, $h2Size, $headingLineHeight, 'h2');
    $this->checkLineHeightInPreview($i, $h3Size, $headingLineHeight, 'h3');
  }

  private function checkLineHeightOptions(\AcceptanceTester $i, $selector) {
    $i->see('1.0', $selector);
    $i->see('1.2', $selector);
    $i->see('1.4', $selector);
    $i->see('1.6', $selector);
    $i->see('1.8', $selector);
    $i->see('2.0', $selector);
  }

  private function checkLineHeightInEditor(\AcceptanceTester $i, $fontSize, $lineHeight, $selector) {
    $elementLineHeight = (float)$i->executeInSelenium(function (WebDriver $webdriver) use ($selector) {
      return $webdriver
        ->findElement(WebDriverBy::cssSelector('.mailpoet_newsletter_wrapper .mailpoet_text_block ' . $selector))
        ->getCSSValue('line-height');
    });
    expect($elementLineHeight)->equals((int)$fontSize * (float)$lineHeight);
  }

  private function checkLineHeightInPreview(\AcceptanceTester $i, $fontSize, $lineHeight, $selector) {
    $i->seeInSource(
      'line-height:' . ((int)$fontSize * (float)$lineHeight) . 'px',
      '.mailpoet_browser_preview_iframe .mailpoet-wrapper .mailpoet_text_block ' . $selector
    );
  }
}
