<?php
namespace MailPoet\Newsletter\Editor;

use MailPoet\Newsletter\Editor\PostContentManager;
use MailPoet\Newsletter\Editor\MetaInformationManager;
use MailPoet\Newsletter\Editor\StructureTransformer;

if(!defined('ABSPATH')) exit;

class PostTransformer {

  function __construct($args) {
    $this->args = $args;
  }

  function transform($post) {
    $content_manager = new PostContentManager($post);
    $meta_manager = new MetaInformationManager();

    $content = $content_manager->getContent($post, $this->args['displayType']);
    $content = $meta_manager->appendMetaInformation($content, $post, $this->args);
    $content = $content_manager->filterContent($content);

    $structure_transformer = new StructureTransformer();
    $structure = $structure_transformer->transform($content, $this->args['imageFullWidth'] === true);

    if($this->args['featuredImagePosition'] === 'aboveTitle') {
      $structure = $this->appendPostTitle($post, $structure);
      $structure = $this->appendFeaturedImage(
        $post,
        $this->args['displayType'],
        filter_var($this->args['imageFullWidth'], FILTER_VALIDATE_BOOLEAN),
        $structure
      );
    } else {
      if($this->args['featuredImagePosition'] === 'belowTitle') {
        $structure = $this->appendFeaturedImage(
          $post,
          $this->args['displayType'],
          filter_var($this->args['imageFullWidth'], FILTER_VALIDATE_BOOLEAN),
          $structure
        );
      }
      $structure = $this->appendPostTitle($post, $structure);
    }
    $structure = $this->appendReadMore($post->ID, $structure);

    return $structure;
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
    if(has_post_thumbnail($post_id)) {
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
  }

  private function appendPostTitle($post, $structure) {
    $title = $this->getPostTitle($post);

    // Append title always at the top of the post structure
    // Reuse an existing text block if needed

    if(count($structure) > 0 && $structure[0]['type'] === 'text') {
      $structure[0]['text'] = $title . $structure[0]['text'];
    } else {
      array_unshift(
        $structure,
        array(
          'type' => 'text',
          'text' => $title,
        )
      );
    }

    return $structure;
  }

  private function appendReadMore($post_id, $structure) {
    if($this->args['readMoreType'] === 'button') {
      $button = $this->args['readMoreButton'];
      $button['url'] = get_permalink($post_id);
      $structure[] = $button;
    } else {
      $total_blocks = count($structure);
      $read_more_text = sprintf(
        '<p><a href="%s">%s</a></p>',
        get_permalink($post_id),
        $this->args['readMoreText']
      );

      if($structure[$total_blocks - 1]['type'] === 'text') {
        $structure[$total_blocks - 1]['text'] .= $read_more_text;
      } else {
        $structure[] = array(
          'type' => 'text',
          'text' => $read_more_text,
        );
      }
    }

    return $structure;
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

    return '<' . $tag . ' data-post-id="' . $post->ID . '" style="text-align: ' . $alignment . ';">' . $title . '</' . $tag . '>';
  }
}
