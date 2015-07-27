<?php
use \AcceptanceTester;

class FormWidgetCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->login();
    }

    public function _after(AcceptanceTester $I)
    {
    }

    // tests
    public function itHasAWidget(AcceptanceTester $I)
    {
        $I->amOnPage('/wp-admin/widgets.php');
        $I->see('MailPoet Subscription Form');
    }
}
