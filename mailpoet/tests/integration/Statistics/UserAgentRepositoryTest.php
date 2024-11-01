<?php declare(strict_types = 1);

namespace integration\Statistics;

class UserAgentRepositoryTest extends \MailPoetTest {

  private $testee;

  public function _before() {
    $this->testee = $this->diContainer->get(\MailPoet\Statistics\UserAgentsRepository::class);
  }

  public function testItCreatesAUserAgent() {
    $userAgent = 'testItCreatesAUserAgent';
    $userAgentEntity = $this->testee->create($userAgent);
    verify($userAgentEntity->getUserAgent())->equals($userAgent);
  }

  public function testItFindsOrCreateUserAgent() {
    $userAgent = 'testItFindsOrCreateUserAgent';
    $userAgentEntity = $this->testee->findOrCreate($userAgent);
    verify($userAgentEntity->getUserAgent())->equals($userAgent);

    $userAgentEntity = $this->testee->findOrCreate($userAgent);
    verify($userAgentEntity->getUserAgent())->equals($userAgent);
  }

  public function testItDoesNotFailWhenTryingToCreateAnExistingUserAgent() {
    $userAgent = 'testItDoesNotFailWhenTryingToCreateAnExistingUserAgent_1';
    $userAgentEntity = $this->testee->create($userAgent);
    verify($userAgentEntity->getUserAgent())->equals($userAgent);

    $userAgentEntity = $this->testee->create($userAgent);
    verify($userAgentEntity->getUserAgent())->equals($userAgent);

    $userAgent = 'testItDoesNotFailWhenTryingToCreateAnExistingUserAgent_2';
    $userAgentEntity = $this->testee->create($userAgent);
    verify($userAgentEntity->getUserAgent())->equals($userAgent);
  }
}
