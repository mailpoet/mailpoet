<?php
use \AcceptanceTester;

class FormWidgetCest {
    public function _before(AcceptanceTester $I) {
        $I->login();
    }

    public function _after(AcceptanceTester $I) {
    }

    // tests
    public function iCanAddAWidget(AcceptanceTester $I) {
        $I->amOnPage('/wp-admin/widgets.php');
        $I->see('MailPoet Subscription Form');

        $I->click('.ui-draggable[id*="mailpoet_form"] h4');

        $I->click('Add Widget');

        $I->seeElement('#widgets-right .widget[id*="mailpoet_form"]');

        $I->click('Delete', '#widgets-right .widget[id*="mailpoet_form"]');
    }
}
