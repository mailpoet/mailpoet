<?php

namespace MailPoet\Test\Acceptance;

use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use MailPoet\Test\DataFactories\Newsletter;

class EditorLineHeightCest {
  public function changeLineHeight(\AcceptanceTester $I) {
    $textSize = '10';
    $h1Size = '40';
    $h2Size = '30';
    $h3Size = '20';
    $textLineHeight = '1.2';
    $headingLineHeight = '2.0';

    $I->wantTo('Edit line height settings in a newsletter');
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithText.json')
      ->create();

    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->click('.mailpoet_styles_region');

    // set text sizes
    $I->selectOption('text-size', $textSize . 'px');
    $I->selectOption('h1-size', $h1Size . 'px');
    $I->selectOption('h2-size', $h2Size . 'px');
    $I->selectOption('h3-size', $h3Size . 'px');

    // check & set line heights
    $this->checkLineHeightOptions($I, '#mailpoet_text_line_height');
    $this->checkLineHeightOptions($I, '#mailpoet_heading_line_height');
    $I->selectOption('#mailpoet_text_line_height', $textLineHeight);
    $I->selectOption('#mailpoet_heading_line_height', $headingLineHeight);

    // check Editor
    $this->checkLineHeightInEditor($I, $textSize, $textLineHeight, 'p');
    $this->checkLineHeightInEditor($I, $h1Size, $headingLineHeight, 'h1');
    $this->checkLineHeightInEditor($I, $h2Size, $headingLineHeight, 'h2');
    $this->checkLineHeightInEditor($I, $h3Size, $headingLineHeight, 'h3');

    // check rendered Preview
    $I->click('.mailpoet_preview_region');
    $I->waitForElementClickable('.mailpoet_show_preview');
    $I->click('.mailpoet_show_preview');
    $I->waitForElement('#mailpoet_browser_preview_iframe');
    $I->switchToIframe('mailpoet_browser_preview_iframe');
    $this->checkLineHeightInPreview($I, $textSize, $textLineHeight, 'p');
    $this->checkLineHeightInPreview($I, $h1Size, $headingLineHeight, 'h1');
    $this->checkLineHeightInPreview($I, $h2Size, $headingLineHeight, 'h2');
    $this->checkLineHeightInPreview($I, $h3Size, $headingLineHeight, 'h3');
  }

  private function checkLineHeightOptions(\AcceptanceTester $I, $selector) {
    $I->see('1.0', $selector);
    $I->see('1.2', $selector);
    $I->see('1.4', $selector);
    $I->see('1.6', $selector);
    $I->see('1.8', $selector);
    $I->see('2.0', $selector);
  }

  private function checkLineHeightInEditor(\AcceptanceTester $I, $fontSize, $lineHeight, $selector) {
    $elementLineHeight = (float)$I->executeInSelenium(function (WebDriver $webdriver) use ($selector) {
      return $webdriver
        ->findElement(WebDriverBy::cssSelector('.mailpoet_newsletter_wrapper .mailpoet_text_block ' . $selector))
        ->getCSSValue('line-height');
    });
    expect($elementLineHeight)->equals((int)$fontSize * (float)$lineHeight);
  }

  private function checkLineHeightInPreview(\AcceptanceTester $I, $fontSize, $lineHeight, $selector) {
    $I->seeInSource(
      'line-height:' . ((int)$fontSize * (float)$lineHeight) . 'px',
      '.mailpoet_browser_preview_iframe .mailpoet-wrapper .mailpoet_text_block ' . $selector
    );
  }
}
