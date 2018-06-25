<?php
namespace MailPoet\Newsletter\Editor;

use MailPoet\Newsletter\Editor\PostContentManager;
use MailPoet\Newsletter\Editor\MetaInformationManager;
use MailPoet\Newsletter\Editor\StructureTransformer;
use MailPoet\Newsletter\Editor\LayoutHelper;
use MailPoet\WP\Functions as WPFunctions;

if(!defined('ABSPATH')) exit;

class PostTransformer {

  private $args;
  private $with_layout;
  private $image_position;

  function __construct($args) {
    $this->args = $args;
    $this->with_layout = isset($args['withLayout']) ? (bool)filter_var($args['withLayout'], FILTER_VALIDATE_BOOLEAN) : false;
    $this->image_position = 'left';
  }

  function getDivider() {
    if(empty($this->with_layout)) {
      return $this->args['divider'];
    }
    return LayoutHelper::row(array(
      LayoutHelper::col(array($this->args['divider']))
    ));
  }

  function transform($post) {
    if(empty($this->with_layout)) {
      return $this->getStructure($post);
    }
    return $this->getStructureWithLayout($post);
  }

  private function getStructure($post) {
    $content = $this->getContent($post, true, $this->args['displayType']);
    $title = $this->getTitle($post);
    $featured_image = $this->getFeaturedImage($post);
    $featured_image_position = $this->args['featuredImagePosition'];

    if($featured_image && $featured_image_position === 'belowTitle' && $this->args['displayType'] === 'excerpt') {
      array_unshift($content, $title, $featured_image);
      return $content;
    }

    if($content[0]['type'] === 'text') {
      $content[0]['text'] = $title['text'] . $content[0]['text'];
    } else {
      array_unshift($content, $title);
    }

    if($featured_image && $this->args['displayType'] === 'excerpt') {
      array_unshift($content, $featured_image);
    }

    return $content;
  }

  private function getStructureWithLayout($post) {
    $content = $this->getContent($post, false, $this->args['displayType']);
    $title = $this->getTitle($post);
    $featured_image = $this->getFeaturedImage($post);

    $featured_image_position = $this->args['featuredImagePosition'];

    if(!$featured_image || $featured_image_position === 'none' || $this->args['displayType'] !== 'excerpt') {
      array_unshift($content, $title);

      return array(
        LayoutHelper::row(array(
          LayoutHelper::col($content)
        ))
      );
    }

    if($featured_image_position === 'aboveTitle' || $featured_image_position === 'belowTitle') {
      $featured_image_position = 'centered';
    }

    if($featured_image_position === 'centered') {
      array_unshift($content, $title, $featured_image);
      return array(
        LayoutHelper::row(array(
          LayoutHelper::col($content)
        ))
      );
    }

    if($featured_image_position === 'alternate') {
      $featured_image_position = $this->nextImagePosition();
    }

    $content = ($featured_image_position === 'left')
      ? array(
        LayoutHelper::col(array($featured_image)),
        LayoutHelper::col($content)
      )
      : array(
        LayoutHelper::col($content),
        LayoutHelper::col(array($featured_image))
      );

    return array(
      LayoutHelper::row(array(
        LayoutHelper::col(array($title))
      )),
      LayoutHelper::row($content)
    );
  }

  private function nextImagePosition() {
    $this->image_position = ($this->image_position === 'left') ? 'right' : 'left';
    return $this->image_position;
  }

  private function getContent($post, $with_post_class, $display_type) {
    $content_manager = new PostContentManager();
    $meta_manager = new MetaInformationManager();

    $content = $content_manager->getContent($post, $this->args['displayType']);
    $content = $meta_manager->appendMetaInformation($content, $post, $this->args);
    $content = $content_manager->filterContent($content, $display_type, $with_post_class);

    $structure_transformer = new StructureTransformer();
    $content = $structure_transformer->transform($content, $this->args['imageFullWidth'] === true);

    $read_more_btn = $this->getReadMoreButton($post);
    $blocks_count = count($content);
    if($read_more_btn['type'] === 'text' && $blocks_count > 0 && $content[$blocks_count - 1]['type'] === 'text') {
      $content[$blocks_count - 1]['text'] .= $read_more_btn['text'];
    } else {
      $content[] = $read_more_btn;
    }
    return $content;
  }

  private function getFeaturedImage($post) {
    $post_id = $post->ID;
    $post_title = $post->post_title;
    $image_full_width = (bool)filter_var($this->args['imageFullWidth'], FILTER_VALIDATE_BOOLEAN);

    if(!has_post_thumbnail($post_id)) {
      return false;
    }

    $thumbnail_id = get_post_thumbnail_id($post_id);
    $image_info = WPFunctions::getImageInfo($thumbnail_id);

    // get alt text
    $alt_text = trim(strip_tags(get_post_meta(
      $thumbnail_id,
      '_wp_attachment_image_alt',
      true
    )));
    if(strlen($alt_text) === 0) {
      // if the alt text is empty then use the post title
      $alt_text = trim(strip_tags($post_title));
    }

    return array(
      'type' => 'image',
      'link' => get_permalink($post_id),
      'src' => $image_info[0],
      'alt' => $alt_text,
      'fullWidth' => $image_full_width,
      'width' => $image_info[1],
      'height' => $image_info[2],
      'styles' => array(
        'block' => array(
          'textAlign' => 'center',
        ),
      ),
    );
  }

  private function getReadMoreButton($post) {
    if($this->args['readMoreType'] === 'button') {
      $button = $this->args['readMoreButton'];
      $button['url'] = get_permalink($post->ID);
      return $button;
    }

    $read_more_text = sprintf(
      '<p><a href="%s">%s</a></p>',
      get_permalink($post->ID),
      $this->args['readMoreText']
    );

    return array(
      'type' => 'text',
      'text' => $read_more_text,
    );
  }

  private function getTitle($post) {
    $title = $post->post_title;

    if(filter_var($this->args['titleIsLink'], FILTER_VALIDATE_BOOLEAN)) {
      $title = '<a href="' . get_permalink($post->ID) . '">' . $title . '</a>';
    }

    if(in_array($this->args['titleFormat'], array('h1', 'h2', 'h3'))) {
      $tag = $this->args['titleFormat'];
    } elseif($this->args['titleFormat'] === 'ul') {
      $tag = 'li';
    } else {
      $tag = 'h1';
    }

    $alignment = (in_array($this->args['titleAlignment'], array('left', 'right', 'center'))) ? $this->args['titleAlignment'] : 'left';

    $title = '<' . $tag . ' data-post-id="' . $post->ID . '" style="text-align: ' . $alignment . ';">' . $title . '</' . $tag . '>';
    return array(
      'type' => 'text',
      'text' => $title
    );
  }

}
