<?php
namespace MailPoet\Config\PopulatorData\Templates;

if(!defined('ABSPATH')) exit;

class Phone {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/phone';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("New Phone Purchase", 'mailpoet'),
      'categories' => json_encode(array('welcome', 'all')),
      'readonly' => 1,
      'thumbnail' => $this->getThumbnail(),
      'body' => json_encode($this->getBody()),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/phone.jpg';
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
                          'backgroundColor' => '#1b1821',
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
                                          'height' => '40px',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Phone-Logo.png',
                                  'alt' => 'Phone-Logo',
                                  'fullWidth' => true,
                                  'width' => '122px',
                                  'height' => '23px',
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
                          'backgroundColor' => '#1b1821',
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
                                  'src' => $this->template_image_url . '/Phone-Title.png',
                                  'alt' => 'Phone-Title',
                                  'fullWidth' => true,
                                  'width' => '1280px',
                                  'height' => '215px',
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
                                  'src' => $this->template_image_url . '/Phone-Header.jpg',
                                  'alt' => 'Phone-Header',
                                  'fullWidth' => true,
                                  'width' => '1280px',
                                  'height' => '920px',
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
                                          'backgroundColor' => 'transparent',
                                          'height' => '30px',
                                        ),
                                    ),
                                ),
                              3 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<h3 style="text-align: center;"><span style="color: #ffffff;"><strong>Welcome to your brand new Casia 7200.</strong></span></h3>
<p style="text-align: center;"><strong><span style="color: #8748d5;">Let\'s get you all set up.</span></strong></p>
<p style="text-align: center;"><span style="color: #999999;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur nec nisi quis ex pulvinar molestie. Sed pulvinar placerat justo eu viverra. Pellentesque in interdum eros, a venenatis velit. Fusce finibus convallis augue, ut viverra felis placerat in.</span></p>',
                                ),
                              4 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'Get Started',
                                  'url' => '',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#8748d5',
                                          'borderColor' => '#0074a2',
                                          'borderWidth' => '0px',
                                          'borderRadius' => '40px',
                                          'borderStyle' => 'solid',
                                          'width' => '159px',
                                          'lineHeight' => '45px',
                                          'fontColor' => '#ffffff',
                                          'fontFamily' => 'Arial',
                                          'fontSize' => '20px',
                                          'fontWeight' => 'bold',
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                              5 =>
                                array (
                                  'type' => 'divider',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => 'transparent',
                                          'padding' => '13px',
                                          'borderStyle' => 'dashed',
                                          'borderWidth' => '2px',
                                          'borderColor' => '#2c2c2c',
                                        ),
                                    ),
                                ),
                              6 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => 'transparent',
                                          'height' => '30px',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
              2 =>
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
                                  'text' => '<p style="font-size: 11px; text-align: left;"><span style="color: #999999;">Address Line 1</span></p>
<p style="font-size: 11px; text-align: left;"><span style="color: #999999;">Address Line 2</span></p>
<p style="font-size: 11px; text-align: left;"><span style="color: #999999;">City</span></p>
<p style="font-size: 11px; text-align: left;"><span style="color: #999999;">Country</span></p>',
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
                                  'src' => $this->template_image_url . '/Phone-Logo.png',
                                  'alt' => 'Phone-Logo',
                                  'fullWidth' => false,
                                  'width' => '122px',
                                  'height' => '23px',
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
                                  'type' => 'social',
                                  'iconSet' => 'full-symbol-grey',
                                  'icons' =>
                                    array (
                                      0 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'facebook',
                                          'link' => 'http://www.facebook.com',
                                          'image' => $this->social_icon_url . '/08-full-symbol-grey/Facebook.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Facebook',
                                        ),
                                      1 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'twitter',
                                          'link' => 'http://www.twitter.com',
                                          'image' => $this->social_icon_url . '/08-full-symbol-grey/Twitter.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Twitter',
                                        ),
                                      2 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'pinterest',
                                          'link' => 'http://www.pinterest.com',
                                          'image' => $this->social_icon_url . '/08-full-symbol-grey/Pinterest.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Pinterest',
                                        ),
                                      3 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'linkedin',
                                          'link' => 'http://www.linkedin.com',
                                          'image' => $this->social_icon_url . '/08-full-symbol-grey/LinkedIn.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'LinkedIn',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                      2 =>
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
                                  'text' => '<p style="text-align: right; font-size: 11px;"><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a></p>
<p style="text-align: right; font-size: 11px;"><a href="[link:subscription_manage_url]">Manage subscription</a></p>',
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
              'fontSize' => '30px',
            ),
          'h2' =>
            array (
              'fontColor' => '#222222',
              'fontFamily' => 'Arial',
              'fontSize' => '24px',
            ),
          'h3' =>
            array (
              'fontColor' => '#333333',
              'fontFamily' => 'Arial',
              'fontSize' => '22px',
            ),
          'link' =>
            array (
              'fontColor' => '#8748d5',
              'textDecoration' => 'underline',
            ),
          'wrapper' =>
            array (
              'backgroundColor' => '#1b1821',
            ),
          'body' =>
            array (
              'backgroundColor' => '#1b1821',
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
              'amount' => '5',
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
                ),
              'backgroundColor' => '#ffffff',
              'backgroundColorAlternate' => '#eeeeee',
            ),
          'button' =>
            array (
              'text' => 'Button',
              'url' => '',
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
              'iconSet' => 'default',
              'icons' =>
                array (
                  0 =>
                    array (
                      'type' => 'socialIcon',
                      'iconType' => 'facebook',
                      'link' => 'http://www.facebook.com',
                      'image' => $this->social_icon_url . '/01-social/Facebook.png?mailpoet_version=3.9.1',
                      'height' => '32px',
                      'width' => '32px',
                      'text' => 'Facebook',
                    ),
                  1 =>
                    array (
                      'type' => 'socialIcon',
                      'iconType' => 'twitter',
                      'link' => 'http://www.twitter.com',
                      'image' => $this->social_icon_url . '/01-social/Twitter.png?mailpoet_version=3.9.1',
                      'height' => '32px',
                      'width' => '32px',
                      'text' => 'Twitter',
                    ),
                ),
            ),
          'spacer' =>
            array (
              'styles' =>
                array (
                  'block' =>
                    array (
                      'backgroundColor' => 'transparent',
                      'height' => '40px',
                    ),
                ),
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
            ),
        ),
    );
  }


}
