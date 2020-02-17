<?php

namespace MailPoet\Test\Models;

use MailPoet\Models\NewsletterPost;
use MailPoetVendor\Carbon\Carbon;

class NewsletterPostTest extends \MailPoetTest {
  public function testItCanGetLatestNewsletterPost() {
    foreach (range(1, 5) as $index) {
      $newsletterPost = NewsletterPost::create();
      $newsletterPost->newsletterId = 1;
      $newsletterPost->postId = $index;
      $newsletterPost->save();
      $newsletterPost->createdAt = Carbon::now()
        ->addMinutes($index);
      $newsletterPost->save();
    }
    $latestNewsletterPost = NewsletterPost::getNewestNewsletterPost(1);
    expect($latestNewsletterPost->postId)->equals(5);
  }

  public function _after() {
    NewsletterPost::deleteMany();
  }
}
