<?php declare(strict_types = 1);

namespace MailPoet\WPCOM;

class DotcomHelperFunctionsTest extends \MailPoetUnitTest {
  /*** @var DotcomHelperFunctions */
  private $dotcomHelper;

  public function _before() {
    parent::_before();
    $this->dotcomHelper = new DotcomHelperFunctions();
  }

  public function testItReturnsFalseIfNotDotcom() {
    verify($this->dotcomHelper->isDotcom())->false();
  }

  public function testItReturnsTrueIfDotcom() {
    define('IS_ATOMIC', true);
    define('ATOMIC_CLIENT_ID', '2');
    verify($this->dotcomHelper->isDotcom())->true();
  }

  public function testItReturnsEmptyStringIfNoPlan() {
    verify($this->dotcomHelper->getDotcomPlan())->equals('');
  }

  public function testItReturnsPerformanceIfWooExpressPerformance() {
    $dotcomHelper = $this->createPartialMock(DotcomHelperFunctions::class, ['isWooExpressPerformance']);
    $dotcomHelper->method('isWooExpressPerformance')->willReturn(true);
    verify($dotcomHelper->getDotcomPlan())->equals('performance');
  }

  public function testItReturnsEssentialIfWooExpressEssential() {
    $dotcomHelper = $this->createPartialMock(DotcomHelperFunctions::class, ['isWooExpressEssential']);
    $dotcomHelper->method('isWooExpressEssential')->willReturn(true);
    verify($dotcomHelper->getDotcomPlan())->equals('essential');
  }

  public function testItReturnsBusinessIfWooBusiness() {
    $dotcomHelper = $this->createPartialMock(DotcomHelperFunctions::class, ['isBusiness']);
    $dotcomHelper->method('isBusiness')->willReturn(true);
    verify($dotcomHelper->getDotcomPlan())->equals('business');
  }

  public function testItReturnsEcommerceTrialIfEcommerceTrial() {
    $dotcomHelper = $this->createPartialMock(DotcomHelperFunctions::class, ['isEcommerceTrial']);
    $dotcomHelper->method('isEcommerceTrial')->willReturn(true);
    verify($dotcomHelper->getDotcomPlan())->equals('ecommerce_trial');
  }

  public function testItReturnsEcommerceWPComIfEcommerceWPCom() {
    $dotcomHelper = $this->createPartialMock(DotcomHelperFunctions::class, ['isEcommerceWPCom']);
    $dotcomHelper->method('isEcommerceWPCom')->willReturn(true);
    verify($dotcomHelper->getDotcomPlan())->equals('ecommerce_wpcom');
  }

  public function testItReturnsEcommerceIfEcommerce() {
    $dotcomHelper = $this->createPartialMock(DotcomHelperFunctions::class, ['isEcommerce']);
    $dotcomHelper->method('isEcommerce')->willReturn(true);
    verify($dotcomHelper->getDotcomPlan())->equals('ecommerce');
  }

  public function testIsGardenReturnsFalseWhenFunctionDoesNotExist() {
    verify($this->dotcomHelper->isGarden())->false();
  }

  public function testGardenNameReturnsNullWhenNotGarden() {
    $dotcomHelper = $this->createPartialMock(DotcomHelperFunctions::class, ['isGarden']);
    $dotcomHelper->method('isGarden')->willReturn(false);
    verify($dotcomHelper->gardenName())->null();
  }

  public function testGardenNameReturnsNullWhenGetSiteMetaNotAvailable() {
    $dotcomHelper = $this->createPartialMock(DotcomHelperFunctions::class, ['isGarden']);
    $dotcomHelper->method('isGarden')->willReturn(true);

    // Mock getSiteMetaValue to return null when get_site_meta is not available
    $dotcomHelper = $this->createPartialMock(DotcomHelperFunctions::class, ['isGarden', 'getSiteMetaValue']);
    $dotcomHelper->method('isGarden')->willReturn(true);
    $dotcomHelper->method('getSiteMetaValue')->willReturn(null);

    verify($dotcomHelper->gardenName())->null();
  }

  public function testGardenNameReturnsNullWhenMetaValueIsEmpty() {
    $dotcomHelper = $this->createPartialMock(DotcomHelperFunctions::class, ['isGarden', 'getSiteMetaValue']);
    $dotcomHelper->method('isGarden')->willReturn(true);
    $dotcomHelper->method('getSiteMetaValue')->willReturn(null);

    verify($dotcomHelper->gardenName())->null();
  }

  public function testGardenNameReturnsValueWhenMetaValueIsValid() {
    $dotcomHelper = $this->createPartialMock(DotcomHelperFunctions::class, ['isGarden', 'getSiteMetaValue']);
    $dotcomHelper->method('isGarden')->willReturn(true);
    $dotcomHelper->method('getSiteMetaValue')->willReturn('My Garden');

    verify($dotcomHelper->gardenName())->equals('My Garden');
  }

  public function testGardenPartnerReturnsNullWhenNotGarden() {
    $dotcomHelper = $this->createPartialMock(DotcomHelperFunctions::class, ['isGarden']);
    $dotcomHelper->method('isGarden')->willReturn(false);
    verify($dotcomHelper->gardenPartner())->null();
  }

  public function testGardenPartnerReturnsNullWhenGetSiteMetaNotAvailable() {
    $dotcomHelper = $this->createPartialMock(DotcomHelperFunctions::class, ['isGarden', 'getSiteMetaValue']);
    $dotcomHelper->method('isGarden')->willReturn(true);
    $dotcomHelper->method('getSiteMetaValue')->willReturn(null);

    verify($dotcomHelper->gardenPartner())->null();
  }

  public function testGardenPartnerReturnsNullWhenMetaValueIsEmpty() {
    $dotcomHelper = $this->createPartialMock(DotcomHelperFunctions::class, ['isGarden', 'getSiteMetaValue']);
    $dotcomHelper->method('isGarden')->willReturn(true);
    $dotcomHelper->method('getSiteMetaValue')->willReturn(null);

    verify($dotcomHelper->gardenPartner())->null();
  }

  public function testGardenPartnerReturnsValueWhenMetaValueIsValid() {
    $dotcomHelper = $this->createPartialMock(DotcomHelperFunctions::class, ['isGarden', 'getSiteMetaValue']);
    $dotcomHelper->method('isGarden')->willReturn(true);
    $dotcomHelper->method('getSiteMetaValue')->willReturn('Partner Name');

    verify($dotcomHelper->gardenPartner())->equals('Partner Name');
  }
}
