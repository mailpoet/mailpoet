<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Newsletter\NewsletterPostsRepository;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;

class AutomatedLatestContentBlockTest extends \MailPoetTest {
  /** @var AutomatedLatestContentBlock */
  private $block;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var NewsletterPostsRepository */
  private $newsletterPostRepository;

  /** @var WPFunctions */
  private $wp;

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

  public function _before() {
    parent::_before();
    $this->block = $this->diContainer->get(AutomatedLatestContentBlock::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
    $this->newsletterPostRepository = $this->diContainer->get(NewsletterPostsRepository::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);

    // Clear old posts
    $posts = $this->wp->getPosts(['post_type' => 'post', 'numberposts' => 0]);
    foreach ($posts as $post) {
      $this->wp->wpDeletePost((int)$post->ID);
    }

    $this->postIds = [];
    $this->postIds[] = $this->createPost('POST 1', '2020-01-01 01:01:01');
    $this->postIds[] = $this->createPost('POST 2', '2020-02-01 01:01:01');
    $this->postIds[] = $this->createPost('POST 3', '2020-03-01 01:01:01');
    $this->postIds[] = $this->createPost('POST 4', '2020-04-01 01:01:01');
  }

  public function testItRendersLatestPostsInAlc() {
    $notification = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_NOTIFICATION);
    $notificationHistory = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_NOTIFICATION_HISTORY, $notification);
    $result = $this->block->render($notificationHistory, $this->alcBlock);
    $encodedResult = json_encode($result);
    expect($encodedResult)->stringContainsString('POST 4');
    expect($encodedResult)->stringContainsString('POST 3');
    expect($encodedResult)->stringNotContainsString('POST 2');
    expect($encodedResult)->stringNotContainsString('POST 1');
  }

  public function testItRendersPostOnlyOncePerEmail() {
    $notification = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_NOTIFICATION);
    $notificationHistory = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_NOTIFICATION_HISTORY, $notification);
    $result = $this->block->render($notificationHistory, $this->alcBlock);
    $encodedResult = json_encode($result);
    expect($encodedResult)->stringContainsString('POST 4');
    expect($encodedResult)->stringContainsString('POST 3');
    expect($encodedResult)->stringNotContainsString('POST 2');
    expect($encodedResult)->stringNotContainsString('POST 1');
    $result = $this->block->render($notificationHistory, $this->alcBlock);
    $encodedResult = json_encode($result);
    expect($encodedResult)->stringNotContainsString('POST 4');
    expect($encodedResult)->stringNotContainsString('POST 3');
    expect($encodedResult)->stringContainsString('POST 2');
    expect($encodedResult)->stringContainsString('POST 1');
  }

  public function testItCanRenderSamePostsForDifferentPostNotifications() {
    $notification = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_NOTIFICATION);
    $notificationHistory = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_NOTIFICATION_HISTORY, $notification);
    $result = $this->block->render($notificationHistory, $this->alcBlock);
    $encodedResult = json_encode($result);
    expect($encodedResult)->stringContainsString('POST 4');
    expect($encodedResult)->stringContainsString('POST 3');
    expect($encodedResult)->stringNotContainsString('POST 2');
    expect($encodedResult)->stringNotContainsString('POST 1');
    $notification2 = $this->createNewsletter('Newsletter2', NewsletterEntity::TYPE_NOTIFICATION);
    $notificationHistory2 = $this->createNewsletter('Newsletter2', NewsletterEntity::TYPE_NOTIFICATION_HISTORY, $notification2);
    $result = $this->block->render($notificationHistory2, $this->alcBlock);
    $encodedResult = json_encode($result);
    expect($encodedResult)->stringContainsString('POST 4');
    expect($encodedResult)->stringContainsString('POST 3');
    expect($encodedResult)->stringNotContainsString('POST 2');
    expect($encodedResult)->stringNotContainsString('POST 1');
  }

  public function testItRendersOnlyPostsNewerThanLastSent() {
    $notification = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_NOTIFICATION);
    $notificationHistory = $this->createNewsletter('Newsletter', NewsletterEntity::TYPE_NOTIFICATION_HISTORY, $notification);
    $newsletterPost = new NewsletterPostEntity($notification, $this->postIds[2]); // Id of POST3
    $this->newsletterPostRepository->persist($newsletterPost);
    $this->newsletterPostRepository->flush();
    $newsletterPost->setCreatedAt(new \DateTime('2020-03-30 01:01:01'));
    $this->newsletterPostRepository->flush();
    $result = $this->block->render($notificationHistory, $this->alcBlock);
    $encodedResult = json_encode($result);
    expect($encodedResult)->stringContainsString('POST 4');
    expect($encodedResult)->stringNotContainsString('POST 3');
    expect($encodedResult)->stringNotContainsString('POST 2');
    expect($encodedResult)->stringNotContainsString('POST 1');
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
}
