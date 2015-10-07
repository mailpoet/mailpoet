<?php

use MailPoet\Mailer\API\AmazonSES;

class AmazonSESCest {
  function __construct() {
    $this->settings = array(
      'name' => 'AmazonSES',
      'type' => 'API',
      'access_key' => 'AKIAJM6Y5HMGXBLDNSRA',
      'secret_key' => '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',
      'region' => 'us-east-1',
    );
    $this->from = 'Sender <vlad@mailpoet.com>';
    $this->mailer = new AmazonSES($this->settings['region'], $this->settings['access_key'], $this->settings['secret_key'], $this->from);
    $this->mailer->subscriber = 'Recipient <mailpoet-test1@mailinator.com>';
    $this->mailer->newsletter = array(
      'subject' => 'testing AmazonSES',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }

  function itsConstructorWorks() {
    expect($this->mailer->awsEndpoint)->equals('email.us-east-1.amazonaws.com');
    expect($this->mailer->url)->equals('https://email.us-east-1.amazonaws.com');
    expect(preg_match('!^\d{8}T\d{6}Z$!', $this->mailer->date))->equals(1);
    expect(preg_match('!^\d{8}$!', $this->mailer->dateWithoutTime))->equals(1);
  }

  function itCanGenerateBody() {
    $body = explode('&', $this->mailer->getBody());
    expect($body[0])
      ->equals('Action=SendEmail');
    expect($body[1])
      ->equals('Version=2010-12-01');
    expect($body[2])
      ->equals('Source=' . $this->from);
    expect($body[3])
      ->contains('Destination.ToAddresses.member.1=' . $this->mailer->subscriber);
    expect($body[4])
      ->equals('Message.Subject.Data=' . $this->mailer->newsletter['subject']);
    expect($body[5])
      ->equals('Message.Body.Html.Data=' . $this->mailer->newsletter['body']['html']);
    expect($body[6])
      ->equals('Message.Body.Text.Data=' . $this->mailer->newsletter['body']['text']);
    expect($body[7])
      ->equals('ReplyToAddresses.member.1=' . $this->from);
    expect($body[8])
      ->equals('ReturnPath=' . $this->from);
  }

  function itCanCreateRequest() {
    $request = $this->mailer->request();
    expect($request['timeout'])
      ->equals(10);
    expect($request['httpversion'])
      ->equals('1.1');
    expect($request['method'])
      ->equals('POST');
    expect($request['headers']['Host'])
      ->equals($this->mailer->awsEndpoint);
    expect($request['headers']['Authorization'])
      ->equals($this->mailer->signRequest($this->mailer->getBody()));
    expect($request['headers']['X-Amz-Date'])
      ->equals($this->mailer->date);
    expect($request['body'])
      ->equals($this->mailer->getBody());
  }

  function itCanCreateCanonicalRequest() {
    $canonicalRequest = explode("\n", $this->mailer->getCanonicalRequest());
    expect($canonicalRequest)
      ->equals(
        array(
          'POST',
          '/',
          '',
          'host:' . $this->mailer->awsEndpoint,
          'x-amz-date:' . $this->mailer->date,
          '',
          'host;x-amz-date',
          hash($this->mailer->hashAlgorithm, $this->mailer->getBody())
        )
      );
  }

  function itCanCreateCredentialScope() {
    $credentialScope = $this->mailer->getCredentialScope();
    expect($credentialScope)
      ->equals(
        $this->mailer->dateWithoutTime . '/' .
        $this->mailer->awsRegion . '/' .
        $this->mailer->awsService . '/' .
        $this->mailer->awsTerminationString
      );
  }

  function itCanCreateStringToSign() {
    $credentialScope = $this->mailer->getCredentialScope();
    $canonicalRequest = $this->mailer->getCanonicalRequest();
    $stringToSing = $this->mailer->createStringToSign(
      $credentialScope,
      $canonicalRequest
    );
    $stringToSing = explode("\n", $stringToSing);
    expect($stringToSing)
      ->equals(
        array(
          $this->mailer->awsSigningAlgorithm,
          $this->mailer->date,
          $credentialScope,
          hash($this->mailer->hashAlgorithm, $canonicalRequest)
        )
      );
  }

  function itCanSignRequest() {
    $signedRequest = $this->mailer->signRequest();
    expect($signedRequest)
      ->contains(
        $this->mailer->awsSigningAlgorithm . ' Credential=' .
        $this->mailer->awsAccessKey . '/' .
        $this->mailer->getCredentialScope() . ', ' .
        'SignedHeaders=host;x-amz-date, Signature='
      );
    expect(preg_match('!Signature=[A-Fa-f0-9]{64}$!', $signedRequest))->equals(1);
  }

  function itCannotSendWithoutProperAccessKey() {
    $mailer = clone $this->mailer;
    $mailer->awsAccessKey = 'somekey';
    $result = $mailer->send(
      $mailer->newsletter,
      $mailer->subscriber
    );
    expect($result)->false();
  }

  function itCanSend() {
    $result = $this->mailer->send(
      $this->mailer->newsletter,
      $this->mailer->subscriber
    );
    expect($result)->true();
  }
}
