<?php

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerce as WooCommerceEmail;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\NewsletterPostsRepository;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Scheduler\AutomaticEmailScheduler;
use MailPoet\WP\Functions as WPFunctions;

class RendererTest extends \MailPoetTest {
  /** @var Renderer */
  private $renderer;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var NewsletterPostsRepository */
  private $newsletterPostRepository;

  /** @var WPFunctions */
  private $wp;

  /** @var AutomaticEmailScheduler */
  private $automaticEmailScheduler;

  /** @var array */
  private $postIds = [];

  private $alcBlock = [
    'type' => 'automatedLatestContentLayout',
    'withLayout' => true,
    'amount' => '2',
    'contentType' => 'post',
    'terms' => [],
    'inclusionType' => 'include',
    'displayType' => 'excerpt',
    'titleFormat' => 'h2',
    'titleAlignment' => 'left',
    'titleIsLink' => false,
    'imageFullWidth' => true,
    'titlePosition' => 'abovePost',
    'featuredImagePosition' => 'left',
    'fullPostFeaturedImagePosition' => 'none',
    'showAuthor' => 'no',
    'authorPrecededBy' => 'Author:',
    'showCategories' => 'no',
    'categoriesPrecededBy' => 'Categories:',
    'readMoreType' => 'button',
    'readMoreText' => 'Read more',
    'readMoreButton' => [
      'type' => 'button',
      'text' => 'Read more',
      'url' => '[postLink]',
      'styles' => [
        'block' => [
            'backgroundColor' => '#e2973f',
            'borderColor' => '#e2973f',
            'borderWidth' => '0px',
            'borderRadius' => '5px',
            'borderStyle' => 'solid',
            'width' => '110px',
            'lineHeight' => '40px',
            'fontColor' => '#ffffff',
            'fontFamily' => 'Arial',
            'fontSize' => '14px',
            'fontWeight' => 'bold',
            'textAlign' => 'left',
          ],
      ],
      'context' => 'automatedLatestContentLayout.readMoreButton',
    ],
    'sortBy' => 'newest',
    'showDivider' => false,
    'divider' => [
      'type' => 'divider',
      'styles' => [
        'block' => [
            'backgroundColor' => 'transparent',
            'padding' => '13px',
            'borderStyle' => 'solid',
            'borderWidth' => '3px',
            'borderColor' => '#aaaaaa',
          ],
        ],
      'context' => 'automatedLatestContentLayout.divider',
    ],
    'backgroundColor' => '#ffffff',
    'backgroundColorAlternate' => '#eeeeee',
  ];

  /** @var array */
  private $productIds = [];

  private $accBlock = [
    'type' => 'abandonedCartContent',
    'amount' => '2',
    'withLayout' => true,
    'contentType' => 'product',
    'postStatus' => 'publish',
    'inclusionType' => 'include',
    'displayType' => 'excerpt',
    'titleFormat' => 'h1',
    'titleAlignment' => 'left',
    'titleIsLink' => false,
    'imageFullWidth' => false,
    'featuredImagePosition' => 'alternate',
    'pricePosition' => 'below',
    'readMoreType' => 'none',
    'readMoreText' => '',
    'readMoreButton' => [],
    'sortBy' => 'newest',
    'showDivider' => true,
    'divider' => [
      'type' => 'divider',
      'context' => 'abandonedCartContent.divider',
      'styles' => [
        'block' => [
            'backgroundColor' => 'transparent',
            'padding' => '13px',
            'borderStyle' => 'solid',
            'borderWidth' => '3px',
            'borderColor' => '#aaaaaa',
          ],
        ],
    ],
    'backgroundColor' => '#ffffff',
    'backgroundColorAlternate' => '#eeeeee',
  ];

  public function _before() {
    parent::_before();
    $this->renderer = $this->diContainer->get(Renderer::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
    $this->newsletterPostRepository = $this->diContainer->get(NewsletterPostsRepository::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->automaticEmailScheduler = new AutomaticEmailScheduler();

    // Clear old posts
    $posts = $this->wp->getPosts(['post_type' => 'post']);
    foreach ($posts as $post) {
      $this->wp->wpDeletePost((int)$post->ID);
    }

    // Clear old products
    $products = $this->wp->getPosts(['post_type' => 'product']);
    foreach ($products as $product) {
      $this->wp->wpDeletePost((int)$product->ID);
    }

    $this->postIds = [];
    $this->postIds[] = $this->createPost('POST 1', '2020-01-01 01:01:01');
    $this->postIds[] = $this->createPost('POST 2', '2020-02-01 01:01:01');
    $this->postIds[] = $this->createPost('POST 3', '2020-03-01 01:01:01');
    $this->postIds[] = $this->createPost('POST 4', '2020-04-01 01:01:01');

    $this->productIds = [];
    $this->productIds[] = $this->createPost('Product 1', '2020-05-01 01:01:01', 'product');
    $this->productIds[] = $this->createPost('Product 2', '2020-06-01 01:01:01', 'product');
    $this->productIds[] = $this->createPost('Product 3', '2020-07-01 01:01:01', 'product');
    $this->productIds[] = $this->createPost('Product 4', '2020-08-01 01:01:01', 'product');
  }

  public function testItRendersLatestPostsInAlc() {
    $notification = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_NOTIFICATION);
    $notificationHistory = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_NOTIFICATION_HISTORY, $notification);
    $result = $this->renderer->automatedLatestContentTransformedPosts($notificationHistory, $this->alcBlock);
    $encodedResult = json_encode($result);
    expect($encodedResult)->contains('POST 4');
    expect($encodedResult)->contains('POST 3');
    expect($encodedResult)->notContains('POST 2');
    expect($encodedResult)->notContains('POST 1');
  }

  public function testItRendersPostOnlyOncePerEmail() {
    $notification = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_NOTIFICATION);
    $notificationHistory = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_NOTIFICATION_HISTORY, $notification);
    $result = $this->renderer->automatedLatestContentTransformedPosts($notificationHistory, $this->alcBlock);
    $encodedResult = json_encode($result);
    expect($encodedResult)->contains('POST 4');
    expect($encodedResult)->contains('POST 3');
    expect($encodedResult)->notContains('POST 2');
    expect($encodedResult)->notContains('POST 1');
    $result = $this->renderer->automatedLatestContentTransformedPosts($notificationHistory, $this->alcBlock);
    $encodedResult = json_encode($result);
    expect($encodedResult)->notContains('POST 4');
    expect($encodedResult)->notContains('POST 3');
    expect($encodedResult)->contains('POST 2');
    expect($encodedResult)->contains('POST 1');
  }

  public function testItCanRenderSamePostsForDifferentPostNotifications() {
    $notification = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_NOTIFICATION);
    $notificationHistory = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_NOTIFICATION_HISTORY, $notification);
    $result = $this->renderer->automatedLatestContentTransformedPosts($notificationHistory, $this->alcBlock);
    $encodedResult = json_encode($result);
    expect($encodedResult)->contains('POST 4');
    expect($encodedResult)->contains('POST 3');
    expect($encodedResult)->notContains('POST 2');
    expect($encodedResult)->notContains('POST 1');
    $notification2 = $this->createNewsletter('Newsletter2', NewsletterEntity::TYPE_NOTIFICATION);
    $notificationHistory2 = $this->createNewsletter('Newsletter2', NewsletterEntity::TYPE_NOTIFICATION_HISTORY, $notification2);
    $result = $this->renderer->automatedLatestContentTransformedPosts($notificationHistory2, $this->alcBlock);
    $encodedResult = json_encode($result);
    expect($encodedResult)->contains('POST 4');
    expect($encodedResult)->contains('POST 3');
    expect($encodedResult)->notContains('POST 2');
    expect($encodedResult)->notContains('POST 1');
  }

  public function testItRendersOnlyPostsNewerThanLastSent() {
    $notification = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_NOTIFICATION);
    $notificationHistory = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_NOTIFICATION_HISTORY, $notification);
    $newsletterPost = new NewsletterPostEntity($notification, $this->postIds[2]); // Id of POST3
    $this->newsletterPostRepository->persist($newsletterPost);
    $this->newsletterPostRepository->flush();
    $newsletterPost->setCreatedAt(new \DateTime('2020-03-30 01:01:01'));
    $this->newsletterPostRepository->flush();
    $result = $this->renderer->automatedLatestContentTransformedPosts($notificationHistory, $this->alcBlock);
    $encodedResult = json_encode($result);
    expect($encodedResult)->contains('POST 4');
    expect($encodedResult)->notContains('POST 3');
    expect($encodedResult)->notContains('POST 2');
    expect($encodedResult)->notContains('POST 1');
  }

  public function testItDoesNotRenderIfNewsletterTypeIsNotAutomatic() {
    $newsletter = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_STANDARD);
    $sendingTask = $this->createSendingTask($newsletter);
    $result = $this->renderer->abandonedCartContentTransformedProducts($newsletter, $this->accBlock, false, $sendingTask);
    $encodedResult = json_encode($result);
    expect($encodedResult)->equals('[]');
  }

  public function testItDoesNotRenderIfAutomaticNewsletterIsNotForAbandonedCart() {
    $newsletter = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_AUTOMATIC);
    $this->setGroupAndEventOptions($newsletter, WooCommerceEmail::SLUG, 'some_event');
    $sendingTask = $this->createSendingTask($newsletter);
    $result = $this->renderer->abandonedCartContentTransformedProducts($newsletter, $this->accBlock, false, $sendingTask);
    $encodedResult = json_encode($result);
    expect($encodedResult)->equals('[]');
  }

  public function testItRendersLatestProductsInPreviewMode() {
    $newsletter = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_AUTOMATIC);
    $this->setGroupAndEventOptions($newsletter);
    $this->accBlock['displayType'] = 'titleOnly';
    $this->accBlock['pricePosition'] = 'hidden';
    $result = $this->renderer->abandonedCartContentTransformedProducts($newsletter, $this->accBlock, true);
    $encodedResult = json_encode($result);
    expect($encodedResult)->contains('Product 4');
    expect($encodedResult)->contains('Product 3');
    expect($encodedResult)->notContains('Product 2');
    expect($encodedResult)->notContains('Product 1');
  }

  public function testItDoesNotRenderIfNoSendingTaskIsSupplied() {
    $newsletter = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_AUTOMATIC);
    $this->setGroupAndEventOptions($newsletter);
    $this->accBlock['displayType'] = 'titleOnly';
    $this->accBlock['pricePosition'] = 'hidden';
    $result = $this->renderer->abandonedCartContentTransformedProducts($newsletter, $this->accBlock, false);
    $encodedResult = json_encode($result);
    expect($encodedResult)->equals('[]');
  }

  public function testItDoesNotRenderIfCartIsEmpty() {
    $newsletter = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_AUTOMATIC);
    $this->setGroupAndEventOptions($newsletter);
    $this->accBlock['displayType'] = 'titleOnly';
    $this->accBlock['pricePosition'] = 'hidden';
    $sendingTask = $this->createSendingTask($newsletter, 1, [AbandonedCart::TASK_META_NAME => []]);
    $result = $this->renderer->abandonedCartContentTransformedProducts($newsletter, $this->accBlock, false, $sendingTask);
    $encodedResult = json_encode($result);
    expect($encodedResult)->equals('[]');
  }

  public function testItRendersAbandonedCartContentBlock() {
    $newsletter = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_AUTOMATIC);
    $this->setGroupAndEventOptions($newsletter);
    $this->accBlock['displayType'] = 'titleOnly';
    $this->accBlock['pricePosition'] = 'hidden';
    $sendingTask = $this->createSendingTask($newsletter);
    $result = $this->renderer->abandonedCartContentTransformedProducts($newsletter, $this->accBlock, false, $sendingTask);
    $encodedResult = json_encode($result);
    expect($encodedResult)->notContains('Product 4');
    expect($encodedResult)->contains('Product 3');
    expect($encodedResult)->contains('Product 2');
    expect($encodedResult)->contains('Product 1');
  }

  private function createPost(string $title, string $publishDate, string $type = 'post') {
    return $this->wp->wpInsertPost([
      'post_title' => $title,
      'post_status' => 'publish',
      'post_date' => $publishDate,
      'post_date_gmt' => $this->wp->getGmtFromDate($publishDate),
      'post_type' => $type,
    ]);
  }

  private function createNewsletter($subject, $type, $parent = null) {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject($subject);
    $newsletter->setType($type);
    $newsletter->setParent($parent);
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    return $newsletter;
  }

  private function setGroupAndEventOptions($newsletter, $group = WooCommerceEmail::SLUG, $event = AbandonedCart::SLUG) {
    $newsletterOptionField = new NewsletterOptionFieldEntity();
    $newsletterOptionField->setName('group');
    $newsletterOptionField->setNewsletterType(NewsletterEntity::TYPE_AUTOMATIC);
    $this->entityManager->persist($newsletterOptionField);

    $newsletterOption = new NewsletterOptionEntity($newsletter, $newsletterOptionField);
    $newsletterOption->setValue($group);
    $this->entityManager->persist($newsletterOption);

    $newsletterOptionField = new NewsletterOptionFieldEntity();
    $newsletterOptionField->setName('event');
    $newsletterOptionField->setNewsletterType(NewsletterEntity::TYPE_AUTOMATIC);
    $this->entityManager->persist($newsletterOptionField);

    $newsletterOption = new NewsletterOptionEntity($newsletter, $newsletterOptionField);
    $newsletterOption->setValue($event);
    $this->entityManager->persist($newsletterOption);

    $this->entityManager->flush($newsletter);
    $this->entityManager->refresh($newsletter);
  }

  private function createSendingTask($newsletter, $subscriberId = null, $meta = null) {
    $subscriberId = $subscriberId ?: 1; // dummy default value
    $meta = $meta ?: [AbandonedCart::TASK_META_NAME => array_slice($this->productIds, 0, 3)];
    $sendingTask = $this->automaticEmailScheduler
      ->createAutomaticEmailSendingTask(Newsletter::findOne($newsletter->getId()), $subscriberId, $meta);
    return $sendingTask;
  }

  public function _after() {
    parent::_after();
    $this->truncateEntity(NewsletterPostEntity::class);
    $this->truncateEntity(NewsletterOptionEntity::class);
    $this->truncateEntity(NewsletterOptionFieldEntity::class);
    $this->truncateEntity(NewsletterEntity::class);
  }
}
