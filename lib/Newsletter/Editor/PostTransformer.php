<?php
namespace MailPoet\Newsletter\Editor;

use MailPoet\Newsletter\Editor\PostContentManager;
use MailPoet\Newsletter\Editor\MetaInformationManager;
use MailPoet\Newsletter\Editor\StructureTransformer;

if(!defined('ABSPATH')) exit;

class PostTransformer {

  private $args;
  private $imagePosition;

  function __construct($args) {
    $this->args = $args;
    $this->imagePosition = 'left';
  }


  function transform($post) {
    $content = $this->getPostContent($post);
    $title = $this->getPostTitle($post);
    $read_more_btn = $this->getReadMoreButton($post);
    $position = $this->args['featuredImagePosition'];
    $featured_image = $this->getFeaturedImage(
      $post->ID,
      $post->post_title,
      (bool) filter_var($this->args['imageFullWidth'], FILTER_VALIDATE_BOOLEAN)
    );
    
    if (!$featured_image || $position === 'none' || $this->args['displayType'] !== 'excerpt') {
      return array(
        array(
          'type' => 'container',
          'orientation' => 'vertical',
          'blocks' => array_merge(
            array($title),
            $content,
            array($read_more_btn)
          )
        )
      );
    }

    if ($position === 'centered') {
      return array(
        array(
          'type' => 'container',
          'orientation' => 'vertical',
          'blocks' => array_merge(
            array($title),
            array($featured_image),
            $content,
            array($read_more_btn)
          )
        )
      );
    }

    if ($position === 'alternate') {
      $position = $this->nextImagePosition();
    }

    $featured_image = array(
      'type' => 'container',
      'orientation' => 'vertical',
      'blocks' => array($featured_image)
    );

    $content = array(
      'type' => 'container',
      'orientation' => 'vertical',
      'blocks' => $content
    );

    return array(
      array(
        'type' => 'container',
        'orientation' => 'vertical',
        'blocks' => array($title)
      ),
      array(
        'type' => 'container',
        'orientation' => 'horizontal',
        'blocks' => ($position === 'left') 
          ? array($featured_image, $content) 
          : array($content, $featured_image)
      ),
      array(
        'type' => 'container',
        'orientation' => 'vertical',
        'blocks' => array($read_more_btn)
      )
    );
  }

  private function nextImagePosition() {
    $this->imagePosition = ($this->imagePosition === 'left') ? 'right' : 'left';
    return $this->imagePosition;
  }

  private function getPostContent($post) {
    $content_manager = new PostContentManager();
    $meta_manager = new MetaInformationManager();

    $content = $content_manager->getContent($post, $this->args['displayType']);
    $content = $meta_manager->appendMetaInformation($content, $post, $this->args);
    $content = $content_manager->filterContent($content);

    $structure_transformer = new StructureTransformer();
    return $structure_transformer->transform($content, $this->args['imageFullWidth'] === true);
  }

  private function appendFeaturedImage($post, $display_type, $image_full_width, $structure) {
    if($display_type !== 'excerpt') {
      // Append featured images only on excerpts
      return $structure;
    }

    $featured_image = $this->getFeaturedImage(
      $post->ID,
      $post->post_title,
      (bool)$image_full_width
    );

    if(is_array($featured_image)) {
      return array_merge(array($featured_image), $structure);
    }

    return $structure;
  }

  private function getFeaturedImage($post_id, $post_title, $image_full_width) {
    if(!has_post_thumbnail($post_id)) {
      return false;
    }

    $thumbnail_id = get_post_thumbnail_id($post_id);

    // get attachment data (src, width, height)
    $image_info = wp_get_attachment_image_src(
      $thumbnail_id,
      'mailpoet_newsletter_max'
    );

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

  private function getPostTitle($post) {
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
