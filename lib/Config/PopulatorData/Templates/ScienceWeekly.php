<?php

namespace MailPoet\Config\PopulatorData\Templates;

class ScienceWeekly {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
     $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/science_weekly';
     $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Science Weekly", 'mailpoet'),
      'categories' => json_encode(array('notification', 'all')),
      'readonly' => 1,
      'thumbnail' => $this->getThumbnail(),
      'body' => json_encode($this->getBody()),
    );
  }

  private function getBody() {
    return array (
      'content' =>
        array (
          'type' => 'container',
          'orientation' => 'vertical',
          'image' =>
            array (
              'src' => NULL,
              'display' => 'scale',
            ),
          'styles' =>
            array (
              'block' =>
                array (
                  'backgroundColor' => 'transparent',
                ),
            ),
          'blocks' =>
            array (
              0 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                    array (
                      'src' => NULL,
                      'display' => 'scale',
                    ),
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#ffffff',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                            array (
                              'src' => NULL,
                              'display' => 'scale',
                            ),
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Science-Logo.png',
                                  'alt' => 'Science-Logo',
                                  'fullWidth' => true,
                                  'width' => '1280px',
                                  'height' => '300px',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Health-Mag-Title-22.png',
                                  'alt' => 'Health-Mag-Title-2',
                                  'fullWidth' => true,
                                  'width' => '1280px',
                                  'height' => '214px',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
              1 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                    array (
                      'src' => NULL,
                      'display' => 'scale',
                    ),
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#b1b6d1',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                            array (
                              'src' => NULL,
                              'display' => 'scale',
                            ),
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<h1 style="text-align: center;"><span style="color: #ffffff;"><strong>The latest news from the world of science</strong></span></h1>',
                                ),
                              1 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#ffffff',
                                          'height' => '40px',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
              2 =>
                array (
                  'type' => 'automatedLatestContentLayout',
                  'withLayout' => true,
                  'amount' => '2',
                  'contentType' => 'post',
                  'terms' =>
                    array (
                    ),
                  'inclusionType' => 'include',
                  'displayType' => 'excerpt',
                  'titleFormat' => 'h1',
                  'titleAlignment' => 'left',
                  'titleIsLink' => false,
                  'imageFullWidth' => false,
                  'featuredImagePosition' => 'alternate',
                  'showAuthor' => 'no',
                  'authorPrecededBy' => 'Author:',
                  'showCategories' => 'no',
                  'categoriesPrecededBy' => 'Categories:',
                  'readMoreType' => 'button',
                  'readMoreText' => 'Read more',
                  'readMoreButton' =>
                    array (
                      'type' => 'button',
                      'text' => 'Read more',
                      'url' => '[postLink]',
                      'styles' =>
                        array (
                          'block' =>
                            array (
                              'backgroundColor' => '#2b2d37',
                              'borderColor' => '#2b2d37',
                              'borderWidth' => '1px',
                              'borderRadius' => '21px',
                              'borderStyle' => 'solid',
                              'width' => '114px',
                              'lineHeight' => '33px',
                              'fontColor' => '#ffffff',
                              'fontFamily' => 'Verdana',
                              'fontSize' => '14px',
                              'fontWeight' => 'normal',
                              'textAlign' => 'left',
                            ),
                        ),
                      'context' => 'automatedLatestContentLayout.readMoreButton',
                    ),
                  'sortBy' => 'newest',
                  'showDivider' => true,
                  'divider' =>
                    array (
                      'type' => 'divider',
                      'styles' =>
                        array (
                          'block' =>
                            array (
                              'backgroundColor' => 'transparent',
                              'padding' => '13px',
                              'borderStyle' => 'solid',
                              'borderWidth' => '3px',
                              'borderColor' => '#aaaaaa',
                            ),
                        ),
                      'context' => 'automatedLatestContentLayout.divider',
                    ),
                  'backgroundColor' => '#ffffff',
                  'backgroundColorAlternate' => '#eeeeee',
                ),
              3 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                    array (
                      'src' => NULL,
                      'display' => 'scale',
                    ),
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => 'transparent',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                            array (
                              'src' => NULL,
                              'display' => 'scale',
                            ),
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#b1b6d1',
                                          'height' => '20px',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Health-Mag-End-1.png',
                                  'alt' => 'Health-Mag-End',
                                  'fullWidth' => true,
                                  'width' => '1280px',
                                  'height' => '50px',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                              2 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#2b2d37',
                                          'height' => '35px',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
              4 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                    array (
                      'src' => NULL,
                      'display' => 'scale',
                    ),
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => 'transparent',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                            array (
                              'src' => NULL,
                              'display' => 'scale',
                            ),
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Health-Mag-Promo-Start.png',
                                  'alt' => 'Health-Mag-Promo-Start',
                                  'fullWidth' => true,
                                  'width' => '1280px',
                                  'height' => '50px',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
              5 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                    array (
                      'src' => NULL,
                      'display' => 'scale',
                    ),
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => 'transparent',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                            array (
                              'src' => NULL,
                              'display' => 'scale',
                            ),
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => 'transparent',
                                          'height' => '50px',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<h2><strong>Download our app!</strong></h2>
                      <p><span>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed in odio dui. Duis et dolor nec erat dictum laoreet. Morbi dapibus turpis id eros viverra tempor. </span></p>
                      <p><span></span></p>
                      <p><span>Fusce et diam ac sapien posuere luctus. Etiam in vehicula metus, ac viverra elit. Duis diam lacus, molestie vel enim non, rutrum placerat massa. Suspendisse a elit tincidunt, egestas lacus at, maximus diam. </span></p>
                      <p><span></span></p>',
                                ),
                              2 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'Download Now',
                                  'url' => '',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#2b2d37',
                                          'borderColor' => '#2b2d37',
                                          'borderWidth' => '1px',
                                          'borderRadius' => '40px',
                                          'borderStyle' => 'solid',
                                          'width' => '144px',
                                          'lineHeight' => '40px',
                                          'fontColor' => '#ffffff',
                                          'fontFamily' => 'Arial',
                                          'fontSize' => '16px',
                                          'fontWeight' => 'normal',
                                          'textAlign' => 'left',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                      1 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                            array (
                              'src' => NULL,
                              'display' => 'scale',
                            ),
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Health-Mag-Phone.png',
                                  'alt' => 'Health-Mag-Phone',
                                  'fullWidth' => false,
                                  'width' => '400px',
                                  'height' => '573px',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
              6 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                    array (
                      'src' => NULL,
                      'display' => 'scale',
                    ),
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => 'transparent',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                            array (
                              'src' => NULL,
                              'display' => 'scale',
                            ),
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Health-Mag-Promo-End.png',
                                  'alt' => 'Health-Mag-Promo-End',
                                  'fullWidth' => true,
                                  'width' => '1280px',
                                  'height' => '50px',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
              7 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                    array (
                      'src' => NULL,
                      'display' => 'scale',
                    ),
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => 'transparent',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                            array (
                              'src' => NULL,
                              'display' => 'scale',
                            ),
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#2b2d37',
                                          'height' => '35px',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Health-Mag-Promo-Start.png',
                                  'alt' => 'Health-Mag-Promo-Start',
                                  'fullWidth' => true,
                                  'width' => '1280px',
                                  'height' => '50px',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
              8 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                    array (
                      'src' => NULL,
                      'display' => 'scale',
                    ),
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => 'transparent',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                            array (
                              'src' => NULL,
                              'display' => 'scale',
                            ),
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<h3 style="text-align: center;">Keep In Touch With Us</h3>',
                                ),
                              1 =>
                                array (
                                  'type' => 'social',
                                  'iconSet' => 'full-symbol-black',
                                  'icons' =>
                                    array (
                                      0 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'facebook',
                                          'link' => 'http://www.facebook.com',
                                          'image' => $this->social_icon_url . '/07-full-symbol-black/Facebook.png?mailpoet_version=3.11.1',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Facebook',
                                        ),
                                      1 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'twitter',
                                          'link' => 'http://www.twitter.com',
                                          'image' => $this->social_icon_url . '/07-full-symbol-black/Twitter.png?mailpoet_version=3.11.1',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Twitter',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
              9 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                    array (
                      'src' => NULL,
                      'display' => 'scale',
                    ),
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => 'transparent',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                            array (
                              'src' => NULL,
                              'display' => 'scale',
                            ),
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Health-Mag-Promo-End.png',
                                  'alt' => 'Health-Mag-Promo-End',
                                  'fullWidth' => true,
                                  'width' => '1280px',
                                  'height' => '50px',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#2b2d37',
                                          'height' => '26px',
                                        ),
                                    ),
                                ),
                              2 =>
                                array (
                                  'type' => 'footer',
                                  'text' => '<p><span style="color: #b1b6d1;"><a href="[link:subscription_unsubscribe_url]" style="color: #b1b6d1;">Unsubscribe</a> | <a href="[link:subscription_manage_url]" style="color: #b1b6d1;">Manage subscription</a></span><br />Add your postal address here!</p>',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#2b2d37',
                                        ),
                                      'text' =>
                                        array (
                                          'fontColor' => '#d6d6d6',
                                          'fontFamily' => 'Arial',
                                          'fontSize' => '12px',
                                          'textAlign' => 'center',
                                        ),
                                      'link' =>
                                        array (
                                          'fontColor' => '#6cb7d4',
                                          'textDecoration' => 'none',
                                        ),
                                    ),
                                ),
                              3 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#2b2d37',
                                          'height' => '40px',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
      'globalStyles' =>
        array (
          'text' =>
            array (
              'fontColor' => '#000000',
              'fontFamily' => 'Arial',
              'fontSize' => '15px',
            ),
          'h1' =>
            array (
              'fontColor' => '#111111',
              'fontFamily' => 'Arial',
              'fontSize' => '26px',
            ),
          'h2' =>
            array (
              'fontColor' => '#222222',
              'fontFamily' => 'Arial',
              'fontSize' => '22px',
            ),
          'h3' =>
            array (
              'fontColor' => '#333333',
              'fontFamily' => 'Arial',
              'fontSize' => '20px',
            ),
          'link' =>
            array (
              'fontColor' => '#21759B',
              'textDecoration' => 'underline',
            ),
          'wrapper' =>
            array (
              'backgroundColor' => '#ffffff',
            ),
          'body' =>
            array (
              'backgroundColor' => '#2b2d37',
            ),
        ),
      'blockDefaults' =>
        array (
          'automatedLatestContent' =>
            array (
              'amount' => '5',
              'withLayout' => false,
              'contentType' => 'post',
              'inclusionType' => 'include',
              'displayType' => 'excerpt',
              'titleFormat' => 'h1',
              'titleAlignment' => 'left',
              'titleIsLink' => false,
              'imageFullWidth' => false,
              'featuredImagePosition' => 'belowTitle',
              'showAuthor' => 'no',
              'authorPrecededBy' => 'Author:',
              'showCategories' => 'no',
              'categoriesPrecededBy' => 'Categories:',
              'readMoreType' => 'button',
              'readMoreText' => 'Read more',
              'readMoreButton' =>
                array (
                  'text' => 'Read more',
                  'url' => '[postLink]',
                  'context' => 'automatedLatestContent.readMoreButton',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#2ea1cd',
                          'borderColor' => '#0074a2',
                          'borderWidth' => '1px',
                          'borderRadius' => '5px',
                          'borderStyle' => 'solid',
                          'width' => '180px',
                          'lineHeight' => '40px',
                          'fontColor' => '#ffffff',
                          'fontFamily' => 'Verdana',
                          'fontSize' => '18px',
                          'fontWeight' => 'normal',
                          'textAlign' => 'center',
                        ),
                    ),
                ),
              'sortBy' => 'newest',
              'showDivider' => true,
              'divider' =>
                array (
                  'context' => 'automatedLatestContent.divider',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => 'transparent',
                          'padding' => '13px',
                          'borderStyle' => 'solid',
                          'borderWidth' => '3px',
                          'borderColor' => '#aaaaaa',
                        ),
                    ),
                ),
              'backgroundColor' => '#ffffff',
              'backgroundColorAlternate' => '#eeeeee',
            ),
          'automatedLatestContentLayout' =>
            array (
              'amount' => '2',
              'withLayout' => true,
              'contentType' => 'post',
              'inclusionType' => 'include',
              'displayType' => 'excerpt',
              'titleFormat' => 'h1',
              'titleAlignment' => 'left',
              'titleIsLink' => false,
              'imageFullWidth' => false,
              'featuredImagePosition' => 'alternate',
              'showAuthor' => 'no',
              'authorPrecededBy' => 'Author:',
              'showCategories' => 'no',
              'categoriesPrecededBy' => 'Categories:',
              'readMoreType' => 'button',
              'readMoreText' => 'Read more',
              'readMoreButton' =>
                array (
                  'text' => 'Read more',
                  'url' => '[postLink]',
                  'context' => 'automatedLatestContentLayout.readMoreButton',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#2b2d37',
                          'borderColor' => '#2b2d37',
                          'borderWidth' => '1px',
                          'borderRadius' => '21px',
                          'borderStyle' => 'solid',
                          'width' => '114px',
                          'lineHeight' => '33px',
                          'fontColor' => '#ffffff',
                          'fontFamily' => 'Verdana',
                          'fontSize' => '14px',
                          'fontWeight' => 'normal',
                          'textAlign' => 'left',
                        ),
                    ),
                  'type' => 'button',
                ),
              'sortBy' => 'newest',
              'showDivider' => true,
              'divider' =>
                array (
                  'context' => 'automatedLatestContentLayout.divider',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => 'transparent',
                          'padding' => '13px',
                          'borderStyle' => 'solid',
                          'borderWidth' => '3px',
                          'borderColor' => '#aaaaaa',
                        ),
                    ),
                  'type' => 'divider',
                ),
              'backgroundColor' => '#ffffff',
              'backgroundColorAlternate' => '#eeeeee',
              'type' => 'automatedLatestContentLayout',
              'terms' =>
                array (
                ),
            ),
          'button' =>
            array (
              'text' => 'Read more',
              'url' => '[postLink]',
              'styles' =>
                array (
                  'block' =>
                    array (
                      'backgroundColor' => '#2ea1cd',
                      'borderColor' => '#0074a2',
                      'borderWidth' => '1px',
                      'borderRadius' => '5px',
                      'borderStyle' => 'solid',
                      'width' => '180px',
                      'lineHeight' => '40px',
                      'fontColor' => '#ffffff',
                      'fontFamily' => 'Verdana',
                      'fontSize' => '18px',
                      'fontWeight' => 'normal',
                      'textAlign' => 'center',
                    ),
                ),
              'type' => 'button',
            ),
          'container' =>
            array (
              'image' =>
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
              'styles' =>
                array (
                  'block' =>
                    array (
                      'backgroundColor' => 'transparent',
                    ),
                ),
            ),
          'divider' =>
            array (
              'styles' =>
                array (
                  'block' =>
                    array (
                      'backgroundColor' => 'transparent',
                      'padding' => '13px',
                      'borderStyle' => 'solid',
                      'borderWidth' => '3px',
                      'borderColor' => '#aaaaaa',
                    ),
                ),
              'type' => 'divider',
            ),
          'footer' =>
            array (
              'text' => '<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage subscription</a><br />Add your postal address here!</p>',
              'styles' =>
                array (
                  'block' =>
                    array (
                      'backgroundColor' => 'transparent',
                    ),
                  'text' =>
                    array (
                      'fontColor' => '#222222',
                      'fontFamily' => 'Arial',
                      'fontSize' => '12px',
                      'textAlign' => 'center',
                    ),
                  'link' =>
                    array (
                      'fontColor' => '#6cb7d4',
                      'textDecoration' => 'none',
                    ),
                ),
            ),
          'posts' =>
            array (
              'amount' => '10',
              'withLayout' => true,
              'contentType' => 'post',
              'postStatus' => 'publish',
              'inclusionType' => 'include',
              'displayType' => 'excerpt',
              'titleFormat' => 'h1',
              'titleAlignment' => 'left',
              'titleIsLink' => false,
              'imageFullWidth' => false,
              'featuredImagePosition' => 'alternate',
              'showAuthor' => 'no',
              'authorPrecededBy' => 'Author:',
              'showCategories' => 'no',
              'categoriesPrecededBy' => 'Categories:',
              'readMoreType' => 'link',
              'readMoreText' => 'Read more',
              'readMoreButton' =>
                array (
                  'text' => 'Read more',
                  'url' => '[postLink]',
                  'context' => 'posts.readMoreButton',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#2ea1cd',
                          'borderColor' => '#0074a2',
                          'borderWidth' => '1px',
                          'borderRadius' => '5px',
                          'borderStyle' => 'solid',
                          'width' => '180px',
                          'lineHeight' => '40px',
                          'fontColor' => '#ffffff',
                          'fontFamily' => 'Verdana',
                          'fontSize' => '18px',
                          'fontWeight' => 'normal',
                          'textAlign' => 'center',
                        ),
                    ),
                ),
              'sortBy' => 'newest',
              'showDivider' => true,
              'divider' =>
                array (
                  'context' => 'posts.divider',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => 'transparent',
                          'padding' => '13px',
                          'borderStyle' => 'solid',
                          'borderWidth' => '3px',
                          'borderColor' => '#aaaaaa',
                        ),
                    ),
                ),
              'backgroundColor' => '#ffffff',
              'backgroundColorAlternate' => '#eeeeee',
            ),
          'social' =>
            array (
              'iconSet' => 'full-symbol-black',
              'icons' =>
                array (
                  0 =>
                    array (
                      'type' => 'socialIcon',
                      'iconType' => 'facebook',
                      'link' => 'http://www.facebook.com',
                      'image' => $this->social_icon_url . '/07-full-symbol-black/Facebook.png?mailpoet_version=3.11.1',
                      'height' => '32px',
                      'width' => '32px',
                      'text' => 'Facebook',
                    ),
                  1 =>
                    array (
                      'type' => 'socialIcon',
                      'iconType' => 'twitter',
                      'link' => 'http://www.twitter.com',
                      'image' => $this->social_icon_url . '/07-full-symbol-black/Twitter.png?mailpoet_version=3.11.1',
                      'height' => '32px',
                      'width' => '32px',
                      'text' => 'Twitter',
                    ),
                ),
              'type' => 'social',
            ),
          'spacer' =>
            array (
              'styles' =>
                array (
                  'block' =>
                    array (
                      'backgroundColor' => '#ffffff',
                      'height' => '40px',
                    ),
                ),
              'type' => 'spacer',
            ),
          'header' =>
            array (
              'text' => 'Display problems?&nbsp;<a href="[link:newsletter_view_in_browser_url]">Open this email in your web browser.</a>',
              'styles' =>
                array (
                  'block' =>
                    array (
                      'backgroundColor' => 'transparent',
                    ),
                  'text' =>
                    array (
                      'fontColor' => '#222222',
                      'fontFamily' => 'Arial',
                      'fontSize' => '12px',
                      'textAlign' => 'center',
                    ),
                  'link' =>
                    array (
                      'fontColor' => '#6cb7d4',
                      'textDecoration' => 'underline',
                    ),
                ),
              'type' => 'header',
            ),
        ),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/science-weekly-1558.jpg';
  }

}
