<?php
namespace MailPoet\Newsletter\Editor;

use \MailPoet\Newsletter\Editor\PostContentManager;
use \MailPoet\Newsletter\Editor\MetaInformationManager;
use \MailPoet\Newsletter\Editor\StructureTransformer;

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
    $structure = $structure_transformer->transform($content, $this->args['imagePadded'] === 'true');

    $structure = $this->appendFeaturedImage(
      $post,
      $this->args['displayType'],
      $this->args['imagePadded'] === 'true',
      $structure
    );
    $structure = $this->appendPostTitle($post, $structure);
    $structure = $this->appendReadMore($post->ID, $structure);

    return $structure;
  }

  private function appendFeaturedImage($post, $display_type, $image_padded, $structure) {
    if ($display_type === 'full') {
      // No featured images for full posts
      return $structure;
    }

    $featured_image = $this->getFeaturedImage(
      $post->ID,
      $post->post_title,
      (bool)$image_padded
    );

    if (is_array($featured_image)) {
      return array_merge(array($featured_image), $structure);
    }

    return $structure;
  }

  private function getFeaturedImage($post_id, $post_title, $image_padded) {
    if(has_post_thumbnail($post_id)) {
      $thumbnail_id = get_post_thumbnail_id($post_id);

      // get attachment data (src, width, height)
      $image_info = wp_get_attachment_image_src(
        $thumbnail_id,
        'single-post-thumbnail'
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
        'padded' => $image_padded,
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

    if ($this->args['titlePosition'] === 'inTextBlock') {
      // Attach title to the first text block
      $text_block_index = null;
      foreach ($structure as $index => $block) {
        if ($block['type'] === 'text') {
          $text_block_index = $index;
          break;
        }
      }

      if ($text_block_index === null) {
        $structure[] = array(
          'type' => 'text',
          'text' => $title,
        );
      } else {
        $structure[$text_block_index]['text'] = $title . $structure[$text_block_index]['text'];
      }
    } elseif ($this->args['titlePosition'] === 'aboveBlock') {
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
    if ($this->args['readMoreType'] === 'button') {
      $button = $this->args['readMoreButton'];
      $button['url'] = get_permalink($post_id);
      $structure[] = $button;
    } else {
      $structure[] = array(
        'type' => 'text',
        'text' => sprintf(
          '<a href="%s">%s</a>',
          get_permalink($post_id),
          $this->args['readMoreText']
        ),
      );
    }

    return $structure;
  }

  private function getPostTitle($post) {
    $title = $post->post_title;

    if ($this->args['titleIsLink'] === 'true') {
      $title = '<a href="' . get_permalink($post->ID) . '">' . $title . '</a>';
    }

    if (in_array($this->args['titleFormat'], array('h1', 'h2', 'h3'))) {
      $tag = $this->args['titleFormat'];
    } elseif ($this->args['titleFormat'] === 'ul') {
      $tag = 'li';
    } else {
      $tag = 'h1';
    }

    $alignment = (in_array($this->args['titleAlignment'], array('left', 'right', 'center'))) ? $this->args['titleAlignment'] : 'left';

    return '<' . $tag . ' style="text-align: ' . $alignment . '">' . $title . '</' . $tag . '>';
  }
}
