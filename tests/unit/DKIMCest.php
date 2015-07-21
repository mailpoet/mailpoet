<?php
use \UnitTester;

class DKIMCest
{

    public function _before(UnitTester $I)
    {

    }

    public function _after(UnitTester $I)
    {

    }

    public function it_can_generate_keys(UnitTester $I) {
        $I->wantTo('generate public and private keys');

        $keys = \MailPoet\Util\DKIM::generate_keys();

        $I->expect('public key is not empty');
        $I->assertNotEmpty($keys['public']);

        $I->expect('private key is not empty');
        $I->assertNotEmpty($keys['private']);

        $I->expect('public key starts with proper header');
        $I->assertTrue(
            strpos(
                $keys['public'],
                '-----BEGIN PUBLIC KEY-----'
            ) === 0
        );

        $I->expect('private key starts with proper header');
        $I->assertTrue(
            strpos(
                $keys['private'],
                '-----BEGIN RSA PRIVATE KEY-----'
            ) === 0
        );
    }
}
