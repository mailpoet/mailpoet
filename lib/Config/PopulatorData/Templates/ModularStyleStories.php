<?php
namespace MailPoet\Config\PopulatorData\Templates;

if(!defined('ABSPATH')) exit;

class ModularStyleStories {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/modular-style-stories';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Modular Style Stories", 'mailpoet'),
      'categories' => json_encode(array('notification', 'all')),
      'readonly' => 1,
      'thumbnail' => $this->getThumbnail(),
      'body' => json_encode($this->getBody()),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/modular-style-stories-1558.jpg';
  }

  private function getBody() {
    return array (
      'content' =>
        array (
          'type' => 'container',
          'columnLayout' => false,
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
                  'columnLayout' => false,
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
                          'backgroundColor' => '#efe7f0',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'columnLayout' => false,
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
                                          'height' => '20px',
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
                  'columnLayout' => false,
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
                          'backgroundColor' => '#efe7f0',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'columnLayout' => false,
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
                                          'height' => '20px',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Modular-Logo.png',
                                  'alt' => 'Modular-Logo',
                                  'fullWidth' => false,
                                  'width' => '271px',
                                  'height' => '37px',
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
                      1 =>
                        array (
                          'type' => 'container',
                          'columnLayout' => false,
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
                                          'height' => '26px',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'social',
                                  'iconSet' => 'full-symbol-color',
                                  'icons' =>
                                    array (
                                      0 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'facebook',
                                          'link' => 'http://www.facebook.com',
                                          'image' => $this->social_icon_url . '/06-full-symbol-color/Facebook.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Facebook',
                                        ),
                                      1 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'twitter',
                                          'link' => 'http://www.twitter.com',
                                          'image' => $this->social_icon_url . '/06-full-symbol-color/Twitter.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Twitter',
                                        ),
                                      2 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'instagram',
                                          'link' => 'http://instagram.com',
                                          'image' => $this->social_icon_url . '/06-full-symbol-color/Instagram.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Instagram',
                                        ),
                                      3 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'pinterest',
                                          'link' => 'http://www.pinterest.com',
                                          'image' => $this->social_icon_url . '/06-full-symbol-color/Pinterest.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Pinterest',
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
                  'columnLayout' => false,
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
                          'columnLayout' => false,
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
                                          'backgroundColor' => '#efe7f0',
                                          'height' => '40px',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
              3 =>
                array (
                  'type' => 'automatedLatestContentLayout',
                  'withLayout' => true,
                  'amount' => '3',
                  'contentType' => 'post',
                  'terms' =>
                    array (
                    ),
                  'inclusionType' => 'include',
                  'displayType' => 'excerpt',
                  'titleFormat' => 'h3',
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
                              'backgroundColor' => '#ffffff',
                              'borderColor' => '#ffffff',
                              'borderWidth' => '1px',
                              'borderRadius' => '0px',
                              'borderStyle' => 'solid',
                              'width' => '120px',
                              'lineHeight' => '40px',
                              'fontColor' => '#b956c5',
                              'fontFamily' => 'Verdana',
                              'fontSize' => '18px',
                              'fontWeight' => 'normal',
                              'textAlign' => 'center',
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
                              'borderStyle' => 'dashed',
                              'borderWidth' => '3px',
                              'borderColor' => '#efe7f0',
                            ),
                        ),
                      'context' => 'automatedLatestContentLayout.divider',
                    ),
                  'backgroundColor' => '#ffffff',
                  'backgroundColorAlternate' => '#eeeeee',
                ),
              4 =>
                array (
                  'type' => 'container',
                  'columnLayout' => false,
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
                          'columnLayout' => false,
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
                                          'backgroundColor' => '#efe7f0',
                                          'height' => '40px',
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
                  'columnLayout' => false,
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
                          'backgroundColor' => '#b956c5',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'columnLayout' => false,
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
                                  'link' => 'http://mailpoet.info/ladybirds-transparent-shell-reveals-how-it-folds-its-wings/',
                                  'src' => $this->template_image_url . '/gettyimages-578313682-800x533.jpg',
                                  'alt' => 'Ladybird’s transparent shell reveals how it folds its wings',
                                  'fullWidth' => false,
                                  'width' => 660,
                                  'height' => 440,
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
                                  'type' => 'text',
                                  'text' => '<h3 style="text-align: left;"><strong>Ladybird&rsquo;s transparent shell reveals how it folds its wings</strong></h3>
<p class="mailpoet_wp_post">They certainly know how to fold. A see-through artificial wing case has been used to watch for the first time as ladybirds put away their wings after flight.</p>',
                                ),
                              2 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'Read More',
                                  'url' => 'http://mailpoet.info/ladybirds-transparent-shell-reveals-how-it-folds-its-wings/',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#b956c5',
                                          'borderColor' => '#000000',
                                          'borderWidth' => '0px',
                                          'borderRadius' => '0px',
                                          'borderStyle' => 'solid',
                                          'width' => '103px',
                                          'lineHeight' => '34px',
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
                          'columnLayout' => false,
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
                                  'link' => 'http://mailpoet.info/plasma-jet-engines-that-could-take-you-from-the-ground-to-space/',
                                  'src' => $this->template_image_url . '/plasma-stingray111-800x533.jpg',
                                  'alt' => 'Plasma jet engines that could take you from the ground to space',
                                  'fullWidth' => false,
                                  'width' => 660,
                                  'height' => 440,
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
                                  'type' => 'text',
                                  'text' => '<h3 style="text-align: left;"><strong>Plasma jet engines that could take you from ground to space</strong></h3>
<p class="mailpoet_wp_post">FORGET fuel-powered jet engines. We&rsquo;re on the verge of having aircraft that can fly from the ground up to the edge of space using air and electricity alone.</p>',
                                ),
                              2 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'Read More',
                                  'url' => 'http://mailpoet.info/plasma-jet-engines-that-could-take-you-from-the-ground-to-space/',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#b956c5',
                                          'borderColor' => '#000000',
                                          'borderWidth' => '0px',
                                          'borderRadius' => '0px',
                                          'borderStyle' => 'solid',
                                          'width' => '103px',
                                          'lineHeight' => '34px',
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
                    ),
                ),
              6 =>
                array (
                  'type' => 'container',
                  'columnLayout' => false,
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
                          'backgroundColor' => '#efe7f0',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'columnLayout' => false,
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
                                          'height' => '30px',
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
                  'columnLayout' => false,
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
                          'backgroundColor' => '#efe7f0',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'columnLayout' => false,
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
                                  'link' => 'http://mailpoet.info/cutting-through-the-smog-what-to-do-to-fight-air-pollution/',
                                  'src' => $this->template_image_url . '/5_what_to_do_p352m1141746-800x533.jpg',
                                  'alt' => 'Cutting through the smog: What to do to fight air pollution',
                                  'fullWidth' => false,
                                  'width' => 660,
                                  'height' => 440,
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
                                  'type' => 'text',
                                  'text' => '<h3 style="text-align: left;"><span style="color: #333333;"><strong>Cutting through the smog: What to do to fight air pollution</strong></span></h3>',
                                ),
                              2 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'Read More',
                                  'url' => 'http://mailpoet.info/cutting-through-the-smog-what-to-do-to-fight-air-pollution/',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#b956c5',
                                          'borderColor' => '#000000',
                                          'borderWidth' => '0px',
                                          'borderRadius' => '0px',
                                          'borderStyle' => 'solid',
                                          'width' => '103px',
                                          'lineHeight' => '34px',
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
                          'columnLayout' => false,
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
                                  'link' => 'http://mailpoet.info/ladybirds-transparent-shell-reveals-how-it-folds-its-wings/',
                                  'src' => $this->template_image_url . '/gettyimages-578313682-800x533.jpg',
                                  'alt' => 'Ladybird’s transparent shell reveals how it folds its wings',
                                  'fullWidth' => false,
                                  'width' => 660,
                                  'height' => 440,
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
                                  'type' => 'text',
                                  'text' => '<h3 style="text-align: left;"><span style="color: #333333;"><strong>Ladybird&rsquo;s transparent shell reveals how it folds its wings</strong></span></h3>',
                                ),
                              2 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'Read More',
                                  'url' => 'http://mailpoet.info/ladybirds-transparent-shell-reveals-how-it-folds-its-wings/',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#b956c5',
                                          'borderColor' => '#000000',
                                          'borderWidth' => '0px',
                                          'borderRadius' => '0px',
                                          'borderStyle' => 'solid',
                                          'width' => '103px',
                                          'lineHeight' => '34px',
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
                      2 =>
                        array (
                          'type' => 'container',
                          'columnLayout' => false,
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
                                  'link' => 'http://mailpoet.info/plasma-jet-engines-that-could-take-you-from-the-ground-to-space/',
                                  'src' => $this->template_image_url . '/plasma-stingray111-800x533.jpg',
                                  'alt' => 'Plasma jet engines that could take you from the ground to space',
                                  'fullWidth' => false,
                                  'width' => 660,
                                  'height' => 440,
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
                                  'type' => 'text',
                                  'text' => '<h3 style="text-align: left;"><span style="color: #333333;"><strong>Plasma jet engines that could take you from the ground to space</strong></span></h3>',
                                ),
                              2 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'Read More',
                                  'url' => 'http://mailpoet.info/plasma-jet-engines-that-could-take-you-from-the-ground-to-space/',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#b956c5',
                                          'borderColor' => '#000000',
                                          'borderWidth' => '0px',
                                          'borderRadius' => '0px',
                                          'borderStyle' => 'solid',
                                          'width' => '103px',
                                          'lineHeight' => '34px',
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
                    ),
                ),
              8 =>
                array (
                  'type' => 'container',
                  'columnLayout' => false,
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
                          'backgroundColor' => '#efe7f0',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'columnLayout' => false,
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
                            ),
                        ),
                    ),
                ),
              9 =>
                array (
                  'type' => 'container',
                  'columnLayout' => false,
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
                          'backgroundColor' => '#b956c5',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'columnLayout' => false,
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
                                          'height' => '21px',
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
                                          'iconType' => 'website',
                                          'link' => '',
                                          'image' => $this->social_icon_url . '/08-full-symbol-grey/Website.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Website',
                                        ),
                                      3 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'instagram',
                                          'link' => 'http://instagram.com',
                                          'image' => $this->social_icon_url . '/08-full-symbol-grey/Instagram.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Instagram',
                                        ),
                                    ),
                                ),
                              2 =>
                                array (
                                  'type' => 'footer',
                                  'text' => '<p><span style="color: #ffffff;"><a href="[link:subscription_unsubscribe_url]" style="color: #ffffff;">Unsubscribe</a> | <a href="[link:subscription_manage_url]" style="color: #ffffff;">Manage subscription</a></span><br /><span style="color: #ffffff;">Add your postal address here!</span></p>',
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
                              3 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => 'transparent',
                                          'height' => '20px',
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
              'fontColor' => '#ffffff',
              'fontFamily' => 'Arial',
              'fontSize' => '14px',
            ),
          'h1' =>
            array (
              'fontColor' => '#ffffff',
              'fontFamily' => 'Arial',
              'fontSize' => '30px',
            ),
          'h2' =>
            array (
              'fontColor' => '#ffffff',
              'fontFamily' => 'Arial',
              'fontSize' => '26px',
            ),
          'h3' =>
            array (
              'fontColor' => '#ffffff',
              'fontFamily' => 'Arial',
              'fontSize' => '20px',
            ),
          'link' =>
            array (
              'fontColor' => '#ffffff',
              'textDecoration' => 'underline',
            ),
          'wrapper' =>
            array (
              'backgroundColor' => '#b956c5',
            ),
          'body' =>
            array (
              'backgroundColor' => '#efe7f0',
            ),
        ),
      'blockDefaults' =>
        array (
          'automatedLatestContent' =>
            array (
              'amount' => '2',
              'contentType' => 'post',
              'inclusionType' => 'include',
              'displayType' => 'excerpt',
              'titleFormat' => 'h2',
              'titleAlignment' => 'left',
              'titleIsLink' => false,
              'imageFullWidth' => true,
              'featuredImagePosition' => 'aboveTitle',
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
                          'backgroundColor' => '#ffffff',
                          'borderColor' => '#0074a2',
                          'borderWidth' => '0px',
                          'borderRadius' => '0px',
                          'borderStyle' => 'solid',
                          'width' => '116px',
                          'lineHeight' => '40px',
                          'fontColor' => '#b956c5',
                          'fontFamily' => 'Arial',
                          'fontSize' => '18px',
                          'fontWeight' => 'normal',
                          'textAlign' => 'center',
                        ),
                    ),
                  'type' => 'button',
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
                          'borderStyle' => 'dashed',
                          'borderWidth' => '3px',
                          'borderColor' => '#ffffff',
                        ),
                    ),
                  'type' => 'divider',
                ),
              'backgroundColor' => '#ffffff',
              'backgroundColorAlternate' => '#eeeeee',
              'type' => 'automatedLatestContent',
              'terms' =>
                array (
                ),
              'withLayout' => false,
            ),
          'automatedLatestContentLayout' =>
            array (
              'amount' => '3',
              'withLayout' => true,
              'contentType' => 'post',
              'inclusionType' => 'include',
              'displayType' => 'excerpt',
              'titleFormat' => 'h3',
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
                          'backgroundColor' => '#ffffff',
                          'borderColor' => '#ffffff',
                          'borderWidth' => '1px',
                          'borderRadius' => '0px',
                          'borderStyle' => 'solid',
                          'width' => '120px',
                          'lineHeight' => '40px',
                          'fontColor' => '#b956c5',
                          'fontFamily' => 'Verdana',
                          'fontSize' => '18px',
                          'fontWeight' => 'normal',
                          'textAlign' => 'center',
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
                          'borderStyle' => 'dashed',
                          'borderWidth' => '3px',
                          'borderColor' => '#efe7f0',
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
              'contentType' => 'post',
              'postStatus' => 'publish',
              'inclusionType' => 'include',
              'displayType' => 'excerpt',
              'titleFormat' => 'h1',
              'titleAlignment' => 'left',
              'titleIsLink' => false,
              'imageFullWidth' => true,
              'featuredImagePosition' => 'aboveTitle',
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
                  'context' => 'posts.readMoreButton',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#ffffff',
                          'borderColor' => '#0074a2',
                          'borderWidth' => '1px',
                          'borderRadius' => '0px',
                          'borderStyle' => 'solid',
                          'width' => '180px',
                          'lineHeight' => '40px',
                          'fontColor' => '#ffffff',
                          'fontFamily' => 'Arial',
                          'fontSize' => '18px',
                          'fontWeight' => 'normal',
                          'textAlign' => 'center',
                        ),
                    ),
                  'type' => 'button',
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
                  'type' => 'divider',
                ),
              'backgroundColor' => '#ffffff',
              'backgroundColorAlternate' => '#eeeeee',
              'type' => 'posts',
              'offset' => 0,
              'terms' =>
                array (
                ),
              'search' => '',
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
                      'image' => $this->social_icon_url . '/01-social/Facebook.png?mailpoet_version=3.7.1',
                      'height' => '32px',
                      'width' => '32px',
                      'text' => 'Facebook',
                    ),
                  1 =>
                    array (
                      'type' => 'socialIcon',
                      'iconType' => 'twitter',
                      'link' => 'http://www.twitter.com',
                      'image' => $this->social_icon_url . '/01-social/Twitter.png?mailpoet_version=3.7.1',
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
                      'backgroundColor' => '#efe7f0',
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
            ),
        ),
    );
  }

}
