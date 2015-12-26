<?php

use MailPoet\Mailer\Methods\AmazonSES;

class AmazonSESCest {
  function _before() {
    $this->settings = array(
      'method' => 'AmazonSES',
      'type' => 'API',
      'access_key' => 'AKIAJM6Y5HMGXBLDNSRA',
      'secret_key' => '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',
      'region' => 'us-east-1',
    );
    $this->from = 'Sender <vlad@mailpoet.com>';
    $this->mailer = new AmazonSES(
      $this->settings['region'],
      $this->settings['access_key'],
      $this->settings['secret_key'],
      $this->from);
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = array(
      'subject' => 'testing AmazonSES',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }

  function itsConstructorWorks() {
    expect($this->mailer->awsEndpoint)
      ->equals(
        sprintf('email.%s.amazonaws.com', $this->settings['region'])
      );
    expect($this->mailer->url)      ->equals(
      sprintf('https://email.%s.amazonaws.com', $this->settings['region'])
    );
    expect(preg_match('!^\d{8}T\d{6}Z$!', $this->mailer->date))->equals(1);
    expect(preg_match('!^\d{8}$!', $this->mailer->dateWithoutTime))->equals(1);
  }

  function itCanGenerateBody() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    expect($body['Action'])->equals('SendEmail');
    expect($body['Version'])->equals('2010-12-01');
    expect($body['Source'])->equals($this->from);
    expect($body['Destination.ToAddresses.member.1'])
      ->contains($this->subscriber);
    expect($body['Message.Subject.Data'])
      ->equals($this->newsletter['subject']);
    expect($body['Message.Body.Html.Data'])
      ->equals($this->newsletter['body']['html']);
    expect($body['Message.Body.Text.Data'])
      ->equals($this->newsletter['body']['text']);
    expect($body['ReturnPath'])->equals($this->from);
  }

  function itCanCreateRequest() {
    $request = $this->mailer->request($this->newsletter, $this->subscriber);
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    expect($request['timeout'])->equals(10);
    expect($request['httpversion'])->equals('1.1');
    expect($request['method'])->equals('POST');
    expect($request['headers']['Host'])->equals($this->mailer->awsEndpoint);
    expect($request['headers']['Authorization'])
      ->equals($this->mailer->signRequest($body));
    expect($request['headers']['X-Amz-Date'])->equals($this->mailer->date);
    expect($request['body'])->equals(urldecode(http_build_query($body)));
  }

  function itCanCreateCanonicalRequest() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    $canonicalRequest = explode(
      "\n",
      $this->mailer->getCanonicalRequest($body)
    );
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
          hash($this->mailer->hashAlgorithm,
               urldecode(http_build_query($body))
          )
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
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    $credentialScope = $this->mailer->getCredentialScope();
    $canonicalRequest = $this->mailer->getCanonicalRequest($body);
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
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    $signedRequest = $this->mailer->signRequest($body);
    expect($signedRequest)
      ->contains(
        $this->mailer->awsSigningAlgorithm . ' Credential=' .
        $this->mailer->awsAccessKey . '/' .
        $this->mailer->getCredentialScope() . ', ' .
        'SignedHeaders=host;x-amz-date, Signature='
      );
    expect(preg_match('!Signature=[A-Fa-f0-9]{64}$!', $signedRequest))
      ->equals(1);
  }

  function itCannotSendWithoutProperAccessKey() {
    $this->mailer->awsAccessKey = 'somekey';
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result)->false();
  }

  function itCanSend() {
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result)->true();
  }
}
