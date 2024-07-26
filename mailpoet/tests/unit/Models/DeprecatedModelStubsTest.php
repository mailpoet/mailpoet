<?php declare(strict_types = 1);

namespace MailPoet\Models;

class DeprecatedModelStubsTest extends \MailPoetUnitTest {
  private array $loggedErrors = [];

  public function _before() {
    parent::_before();
    $this->loggedErrors = [];
    set_error_handler([$this, 'errorHandler'], E_USER_DEPRECATED);
  }

  public function testItThrowsDeprecationErrorInConstruct(): void {
    new Subscriber();
    verify($this->loggedErrors[0])->equals('Calling MailPoet\Models\Subscriber::__construct was deprecated and has been removed.');
  }

  public function testItReturnFalseAndThrowsDeprecationErrorsInFindOne(): void {
    $result = Subscriber::where('id', 1)->findOne();
    verify($result)->false();
    verify($this->loggedErrors[0])->equals('Calling MailPoet\Models\Subscriber::where was deprecated and has been removed.');
    verify($this->loggedErrors[1])->equals('Calling MailPoet\Models\Subscriber::__construct was deprecated and has been removed.');
    verify($this->loggedErrors[2])->equals('Calling MailPoet\Models\Subscriber::findOne was deprecated and has been removed.');
  }

  public function testItReturnsNullAndDeprecationWarningForGetters(): void {
    $subscriber = new Subscriber();
    $result = $subscriber->id;
    verify($result)->null();
    verify($this->loggedErrors[1])->equals('Calling MailPoet\Models\Subscriber::__get was deprecated and has been removed.');
    $result = $subscriber->getId();
    verify($result)->null();
    verify($this->loggedErrors[2])->equals('Calling MailPoet\Models\Subscriber::getId was deprecated and has been removed.');
  }

  public function testItThrowsDeprecationWarningForSetters(): void {
    $subscriber = new Subscriber();
    $subscriber->name = 'John Doe';
    verify($this->loggedErrors[1])->equals('Calling MailPoet\Models\Subscriber::__set was deprecated and has been removed.');
  }

  public function testItSupportsFluentInterfaceForStaticMethods(): void {
    $result = Subscriber::whereIn('id', [1, 2]);
    verify($result)->instanceOf(Subscriber::class);
    $result = Newsletter::create([]);
    verify($result)->instanceOf(Newsletter::class);
  }

  public function testItSupportsFluentInterfaceForInstanceMethods(): void {
    $subscriber = new Subscriber();
    $result = $subscriber->where('id', 1);
    verify($result)->instanceOf(Subscriber::class);
  }

  public function testItReturnsEmptyArrayForMethodsReturningArray(): void {
    $result = Newsletter::findArray();
    verify($result)->equals([]);
    $result = Subscriber::where('status', 'subscribed')->findMany();
    verify($result)->equals([]);
  }

  public function testItRetunsNullAndThrowsDeprecationWarningForUnknownMethods(): void {
    $result = Newsletter::someNonsense();
    verify($result)->null();
    verify($this->loggedErrors[0])->equals('Calling MailPoet\Models\Newsletter::someNonsense was deprecated and has been removed.');
    $newsletter = new Newsletter(); // triggers __construct deprecation error
    $result = $newsletter->someNonsense(); // triggers someNonsense deprecation error
    verify($result)->null();
    verify($this->loggedErrors[2])->equals('Calling MailPoet\Models\Newsletter::someNonsense was deprecated and has been removed.');
  }

  public function testItReturnsProperValuesForFoundKnownUsageCases(): void {
    // https://github.com/deckerweb/toolbar-extras/blob/adc9c7a68e7b5a12413739d22a2eaadee6a05f52/includes/plugins-forms/items-mailpoet.php#L68
    $result = Newsletter::getPublished()->findArray();
    verify($result)->equals([]);
    // https://github.com/UncannyOwl/Uncanny-Automator/blob/b1f95146052cb0de31b9b5a081ccf32bcaa68b93/src/integrations/mailpoet/actions/mailpoet-addsubscribertolist-a.php#L179
    $result = Subscriber::findOne('test@email.com');
    verify($result)->equals(false);
    // https://github.com/the-marketer/wooCommerce/blob/9b8ecd2e7651ab676171377ab70c097920ef6c86/Tracker/Routes/saveOrder.php#L66
    $result = Subscriber::getWooCommerceSegmentSubscriber('test@email.com');
    verify($result)->false();
    // https://github.com/kingfunnel/wp-fusion/blob/da70adff8bbdcdc43b19374fb613b0ce121f5cf1/includes/crms/mailpoet/class-mailpoet.php#L323
    $result = Subscriber::createOrUpdate([]);
    verify($result)->instanceOf(Subscriber::class);
  }

  public function errorHandler($errno, $errstr): void {
    $this->loggedErrors[] = $errstr;
  }

  public function _after(): void {
    restore_error_handler();
  }
}
