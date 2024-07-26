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

  public function errorHandler($errno, $errstr): void {
    $this->loggedErrors[] = $errstr;
  }

  public function _after(): void {
    restore_error_handler();
  }
}
