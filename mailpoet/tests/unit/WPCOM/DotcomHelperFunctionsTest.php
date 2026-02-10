<?php declare(strict_types = 1);

namespace MailPoet\WPCOM;

use MailPoet\WP\Functions as WPFunctions;

class DotcomHelperFunctionsTest extends \MailPoetUnitTest {
  /*** @var DotcomHelperFunctions */
  private $dotcomHelper;

  /*** @var WPFunctions */
  private $wp;

  public function _before() {
    parent::_before();
    $this->wp = $this->createMock(WPFunctions::class);
    $this->wp->expects($this->any())
      ->method('applyFilters')
      ->willReturnCallback(function ($tag, $value) {
        return $value;
      });
    $this->dotcomHelper = new DotcomHelperFunctions($this->wp);
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
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isWooExpressPerformance'])
      ->getMock();
    $dotcomHelper->method('isWooExpressPerformance')->willReturn(true);
    verify($dotcomHelper->getDotcomPlan())->equals('performance');
  }

  public function testItReturnsEssentialIfWooExpressEssential() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isWooExpressEssential'])
      ->getMock();
    $dotcomHelper->method('isWooExpressEssential')->willReturn(true);
    verify($dotcomHelper->getDotcomPlan())->equals('essential');
  }

  public function testItReturnsBusinessIfWooBusiness() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isBusiness'])
      ->getMock();
    $dotcomHelper->method('isBusiness')->willReturn(true);
    verify($dotcomHelper->getDotcomPlan())->equals('business');
  }

  public function testItReturnsEcommerceTrialIfEcommerceTrial() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isEcommerceTrial'])
      ->getMock();
    $dotcomHelper->method('isEcommerceTrial')->willReturn(true);
    verify($dotcomHelper->getDotcomPlan())->equals('ecommerce_trial');
  }

  public function testItReturnsEcommerceWPComIfEcommerceWPCom() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isEcommerceWPCom'])
      ->getMock();
    $dotcomHelper->method('isEcommerceWPCom')->willReturn(true);
    verify($dotcomHelper->getDotcomPlan())->equals('ecommerce_wpcom');
  }

  public function testItReturnsEcommerceIfEcommerce() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isEcommerce'])
      ->getMock();
    $dotcomHelper->method('isEcommerce')->willReturn(true);
    verify($dotcomHelper->getDotcomPlan())->equals('ecommerce');
  }

  public function testIsGardenReturnsFalseWhenFunctionDoesNotExist() {
    verify($this->dotcomHelper->isGarden())->false();
  }

  public function testGardenNameReturnsNullWhenNotGarden() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isGarden'])
      ->getMock();
    $dotcomHelper->method('isGarden')->willReturn(false);
    verify($dotcomHelper->gardenName())->null();
  }

  public function testGardenNameReturnsNullWhenGetSiteMetaNotAvailable() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isGarden', 'getSiteMetaValue'])
      ->getMock();
    $dotcomHelper->method('isGarden')->willReturn(true);
    $dotcomHelper->method('getSiteMetaValue')->willReturn(null);

    verify($dotcomHelper->gardenName())->null();
  }

  public function testGardenNameReturnsNullWhenMetaValueIsEmpty() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isGarden', 'getSiteMetaValue'])
      ->getMock();
    $dotcomHelper->method('isGarden')->willReturn(true);
    $dotcomHelper->method('getSiteMetaValue')->willReturn(null);

    verify($dotcomHelper->gardenName())->null();
  }

  public function testGardenNameReturnsValueWhenMetaValueIsValid() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isGarden', 'getSiteMetaValue'])
      ->getMock();
    $dotcomHelper->method('isGarden')->willReturn(true);
    $dotcomHelper->method('getSiteMetaValue')->willReturn('My Garden');

    verify($dotcomHelper->gardenName())->equals('My Garden');
  }

  public function testGardenPartnerReturnsNullWhenNotGarden() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isGarden'])
      ->getMock();
    $dotcomHelper->method('isGarden')->willReturn(false);
    verify($dotcomHelper->gardenPartner())->null();
  }

  public function testGardenPartnerReturnsNullWhenGetSiteMetaNotAvailable() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isGarden', 'getSiteMetaValue'])
      ->getMock();
    $dotcomHelper->method('isGarden')->willReturn(true);
    $dotcomHelper->method('getSiteMetaValue')->willReturn(null);

    verify($dotcomHelper->gardenPartner())->null();
  }

  public function testGardenPartnerReturnsNullWhenMetaValueIsEmpty() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isGarden', 'getSiteMetaValue'])
      ->getMock();
    $dotcomHelper->method('isGarden')->willReturn(true);
    $dotcomHelper->method('getSiteMetaValue')->willReturn(null);

    verify($dotcomHelper->gardenPartner())->null();
  }

  public function testGardenPartnerReturnsValueWhenMetaValueIsValid() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isGarden', 'getSiteMetaValue'])
      ->getMock();
    $dotcomHelper->method('isGarden')->willReturn(true);
    $dotcomHelper->method('getSiteMetaValue')->willReturn('Partner Name');

    verify($dotcomHelper->gardenPartner())->equals('Partner Name');
  }

  public function testIsDotcomReturnsFalseWhenNeitherPlatform() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isAtomicPlatform', 'isGarden'])
      ->getMock();
    $dotcomHelper->method('isAtomicPlatform')->willReturn(false);
    verify($dotcomHelper->isDotcom())->false();
  }

  public function testGetDotcomPlanReturnsPlanTypeFromGarden() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isGarden', 'getWpcloudConfig'])
      ->getMock();
    $dotcomHelper->method('isGarden')->willReturn(true);
    $dotcomHelper->method('getWpcloudConfig')->willReturn(json_encode(['plan_type' => 'trial']));
    verify($dotcomHelper->getDotcomPlan())->equals('trial');
  }

  public function testGetDotcomPlanReturnsEmptyWhenGardenHasNoPlanType() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isGarden', 'getWpcloudConfig'])
      ->getMock();
    $dotcomHelper->method('isGarden')->willReturn(true);
    $dotcomHelper->method('getWpcloudConfig')->willReturn(null);
    verify($dotcomHelper->getDotcomPlan())->equals('');
  }

  public function testGetDotcomPlanReturnsEmptyWhenGardenHasInvalidJson() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isGarden', 'getWpcloudConfig'])
      ->getMock();
    $dotcomHelper->method('isGarden')->willReturn(true);
    $dotcomHelper->method('getWpcloudConfig')->willReturn('not valid json');
    verify($dotcomHelper->getDotcomPlan())->equals('');
  }

  public function testGetDotcomPlanPrefersWcCalypsoBridgeOverGarden() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isWooExpressPerformance', 'isGarden', 'getWpcloudConfig'])
      ->getMock();
    $dotcomHelper->method('isWooExpressPerformance')->willReturn(true);
    $dotcomHelper->method('isGarden')->willReturn(true);
    $dotcomHelper->method('getWpcloudConfig')->willReturn(json_encode(['plan_type' => 'paid']));
    verify($dotcomHelper->getDotcomPlan())->equals('performance');
  }

  public function testGetSiteMetaValueFallsBackToWpcloudConfigForGarden() {
    $dotcomHelper = new class($this->wp) extends DotcomHelperFunctions {
      public function publicGetSiteMetaValue(string $key): ?string {
        return $this->getSiteMetaValue($key);
      }

      public function isGarden(): bool {
        return true;
      }

      protected function getWpcloudConfig(string $key): ?string {
        if ($key === 'test_key') {
          return 'test_value';
        }
        return null;
      }
    };

    verify($dotcomHelper->publicGetSiteMetaValue('test_key'))->equals('test_value');
    verify($dotcomHelper->publicGetSiteMetaValue('nonexistent_key'))->null();
  }
}
