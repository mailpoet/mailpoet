<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\CustomField as CustomFieldFactory;

class CustomFieldsListingCest {
  public function customFieldsNativeFiltersAndSorting(\AcceptanceTester $i) {
    $i->wantTo('Filter and sort the custom fields listing with native DataViews controls');

    $suffix = 'cffilter' . uniqid();
    $textField = "Text-{$suffix}";
    $dateField = "Date-{$suffix}";

    (new CustomFieldFactory())->withName($textField)->withType('text')->create();
    (new CustomFieldFactory())->withName($dateField)->withType('date')->create();

    $i->login();
    $i->amOnMailpoetPage('custom-fields');
    $i->searchFor($suffix);
    $i->waitForText($textField);
    $i->waitForText($dateField);

    $i->wantTo('Apply the native Type filter and keep only Date fields');
    $i->click(['xpath' => '//button[@aria-label="Add filter"]']);
    $i->waitForText('Type');
    $i->click(['xpath' => '//*[@role="menuitem"][contains(normalize-space(.), "Type")]']);
    $i->waitForElement('.dataviews-filters__search-widget-listitem');
    $i->click(['xpath' => '//*[contains(@class, "dataviews-filters__search-widget-listitem")][.//text()[contains(., "Date")]]']);
    $i->pressKey('body', \Facebook\WebDriver\WebDriverKeys::ESCAPE);
    $i->waitForText($dateField);
    $i->dontSee($textField);
    $i->seeInCurrentUrl('type=date');

    $i->wantTo('Sort the custom fields by name descending');
    $i->amOnMailpoetPage('custom-fields');
    $i->searchFor($suffix);
    $i->waitForText($textField);
    $i->click(['xpath' => '//th//button[contains(., "Name")]']);
    $i->waitForText('Sort descending');
    $i->click(['xpath' => '//*[@role="menuitemradio"][contains(normalize-space(.), "Sort descending")]']);
    $i->waitForText($textField);
    $i->waitForJS(<<<JS
      const text = Array.from(document.querySelectorAll('.mailpoet-custom-fields-dataviews tbody tr'))
        .map((row) => row.textContent)
        .join('||');
      const textAt = text.indexOf('{$textField}');
      const dateAt = text.indexOf('{$dateField}');
      return textAt !== -1 && dateAt !== -1 && textAt < dateAt;
    JS, 10);
  }
}
