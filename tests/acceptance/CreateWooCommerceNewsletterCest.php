<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;

class CreateWooCommerceNewsletterCest {

  function createFirstPurchaseEmail(\AcceptanceTester $I) {
    $I->wantTo('Create and configure a first purchase automatic email');

    $newsletter_title = 'First Purchase Email Creation';
    $I->activateWooCommerce();

    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="create_woocommerce"]');
    $I->click('[data-automation-id="create_woocommerce_first_purchase"]');

    $I->waitForText('Select WooCommerce events conditions');
    $I->click('Next');

    $template = '[data-automation-id="select_template_0"]';
    $I->waitForElement($template);
    $I->click($template);

    $title_element = '[data-automation-id="newsletter_title"]';
    $I->waitForElement($title_element);
    $I->fillField($title_element, $newsletter_title);
    $I->click('Next');

    $I->waitForElement('[data-automation-id="newsletter_send_form"]');
    $newsletter_listing_element = '[data-automation-id="listing_item_' . basename($I->getCurrentUrl()) . '"]';
    $I->waitForElementClickable('[value="Activate"]');
    $I->click('Activate');

    $I->waitForElement($newsletter_listing_element);
    $I->see($newsletter_title, $newsletter_listing_element);
    $I->see('Email sent when a customer makes their first purchase.', $newsletter_listing_element);
  }

  function createAbandonedCartEmail(\AcceptanceTester $I) {
    $I->wantTo('Create and configure an abandoned cart automatic email');

    $newsletter_title = 'Abandoned Cart Email Creation';
    $I->activateWooCommerce();

    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="create_woocommerce"]');
    $I->scrollToTop();
    $I->click('[data-automation-id="create_woocommerce_abandoned_shopping_cart"]');

    $I->waitForText('Abandoned Shopping Cart');
    $I->click('Next');

    $template = '[data-automation-id="select_template_0"]';
    $I->waitForElement($template);
    $I->click($template);

    $title_element = '[data-automation-id="newsletter_title"]';
    $I->waitForElement($title_element);
    $I->fillField($title_element, $newsletter_title);
    $I->click('Next');

    $I->waitForElement('[data-automation-id="newsletter_send_form"]');
    $newsletter_listing_element = '[data-automation-id="listing_item_' . basename($I->getCurrentUrl()) . '"]';
    $I->waitForElementClickable('[value="Activate"]');
    $I->click('Activate');

    $I->waitForElement($newsletter_listing_element);
    $I->see($newsletter_title, $newsletter_listing_element);
    $I->see('Email sent when a customer abandons his cart', $newsletter_listing_element);
  }

}
