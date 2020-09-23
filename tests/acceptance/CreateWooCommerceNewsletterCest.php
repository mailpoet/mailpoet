<?php

namespace MailPoet\Test\Acceptance;

class CreateWooCommerceNewsletterCest {
  public function createFirstPurchaseEmail(\AcceptanceTester $i) {
    $i->wantTo('Create and configure a first purchase automatic email');

    $newsletterTitle = 'First Purchase Email Creation';
    $i->activateWooCommerce();

    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="create_woocommerce_first_purchase"]');

    $i->waitForElement('[data-automation-id="woocommerce_email_creation_heading"]');
    $i->click('Next');

    $template = $i->checkTemplateIsPresent(0, 'woocommerce');
    $i->click($template);

    $titleElement = '[data-automation-id="newsletter_title"]';
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $newsletterTitle);
    $i->click('Next');

    $i->waitForElement('[data-automation-id="newsletter_send_form"]');
    $newsletterListingElement = '[data-automation-id="listing_item_' . basename($i->getCurrentUrl()) . '"]';
    $i->waitForElementClickable('[data-automation-id="email-submit"]');
    $i->click('Activate');

    $i->waitForElement($newsletterListingElement);
    $i->see($newsletterTitle, $newsletterListingElement);
    $i->see('Email sent when a customer makes their first purchase.', $newsletterListingElement);
  }

  public function createAbandonedCartEmail(\AcceptanceTester $i) {
    $i->wantTo('Create and configure an abandoned cart automatic email');

    $newsletterTitle = 'Abandoned Cart Email Creation';
    $i->activateWooCommerce();

    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="create_woocommerce_abandoned_shopping_cart"]');

    $i->waitForText('Abandoned Shopping Cart');
    $i->click('Next');

    $template = $i->checkTemplateIsPresent(0, 'woocommerce');
    $i->click($template);

    $titleElement = '[data-automation-id="newsletter_title"]';
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $newsletterTitle);
    $i->click('Next');

    $i->waitForElement('[data-automation-id="newsletter_send_form"]');
    $newsletterListingElement = '[data-automation-id="listing_item_' . basename($i->getCurrentUrl()) . '"]';
    $i->waitForElementClickable('[data-automation-id="email-submit"]');
    $i->click('Activate');

    $i->waitForElement($newsletterListingElement);
    $i->see($newsletterTitle, $newsletterListingElement);
    $i->see('Email sent when a customer abandons his cart', $newsletterListingElement);
  }
}
