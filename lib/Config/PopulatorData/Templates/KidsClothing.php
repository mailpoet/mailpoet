<?php
namespace MailPoet\Config\PopulatorData\Templates;

if(!defined('ABSPATH')) exit;

class KidsClothing {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/kids-clothing';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Kids Clothing", 'mailpoet'),
      'categories' => json_encode(array('woocommerce', 'all')),
      'readonly' => 1,
      'thumbnail' => $this->getThumbnail(),
      'body' => json_encode($this->getBody()),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/kids-clothing.jpg';
  }

  private function getBody() {
    return array (
      'content' =>
        array (
          'type' => 'container',
          'orientation' => 'vertical',
          'image' =>
            array (
              'display' => 'scale',
              'src' => NULL,
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
                      'display' => 'scale',
                      'src' => NULL,
                    ),
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#c3e1e8',
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
                              'display' => 'scale',
                              'src' => NULL,
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
                  'orientation' => 'horizontal',
                  'image' =>
                    array (
                      'display' => 'scale',
                      'src' => NULL,
                    ),
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#c3e1e8',
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
                              'display' => 'scale',
                              'src' => NULL,
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
                                  'type' => 'social',
                                  'iconSet' => 'circles',
                                  'icons' =>
                                    array (
                                      0 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'facebook',
                                          'link' => 'http://www.facebook.com',
                                          'image' => $this->social_icon_url . '/03-circles/Facebook.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Facebook',
                                        ),
                                      1 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'twitter',
                                          'link' => 'http://www.twitter.com',
                                          'image' => $this->social_icon_url . '/03-circles/Twitter.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Twitter',
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
                              'display' => 'scale',
                              'src' => NULL,
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
                                  'src' => $this->template_image_url . '/Kids-Clothing-Logo.png',
                                  'alt' => 'Kids-Clothing-Logo',
                                  'fullWidth' => true,
                                  'width' => '250px',
                                  'height' => '121px',
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
                      2 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                            array (
                              'display' => 'scale',
                              'src' => NULL,
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
                                  'type' => 'social',
                                  'iconSet' => 'circles',
                                  'icons' =>
                                    array (
                                      0 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'google-plus',
                                          'link' => 'http://plus.google.com',
                                          'image' => $this->social_icon_url . '/03-circles/Google-Plus.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Google Plus',
                                        ),
                                      1 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'instagram',
                                          'link' => 'http://instagram.com',
                                          'image' => $this->social_icon_url . '/03-circles/Instagram.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Instagram',
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
                      'display' => 'scale',
                      'src' => NULL,
                    ),
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#9bd2e0',
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
                              'display' => 'scale',
                              'src' => NULL,
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
              3 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                    array (
                      'display' => 'scale',
                      'src' => NULL,
                    ),
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#9bd2e0',
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
                              'display' => 'scale',
                              'src' => NULL,
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
                                  'text' => '<p style="text-align: center;"><span style="color: #4e4e4e;"><strong>Boys Clothes</strong></span></p>',
                                ),
                            ),
                        ),
                      1 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                            array (
                              'display' => 'scale',
                              'src' => NULL,
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
                                  'text' => '<p style="text-align: center;"><span style="color: #4e4e4e;"><strong>Girls Clothes</strong></span></p>',
                                ),
                            ),
                        ),
                      2 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                            array (
                              'display' => 'scale',
                              'src' => NULL,
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
                                  'text' => '<p style="text-align: center;"><span style="color: #4e4e4e;"><strong>Toys &amp; Games</strong></span></p>',
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
                      'src' => $this->template_image_url . '/Kids-Clothing-Header.jpg',
                      'display' => 'scale',
                    ),
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#9cd1e1',
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
                                          'height' => '80px',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<h1><strong>Wait!</strong></h1>
<h3>You\'ve left something in your cart!</h3>',
                                ),
                              2 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => 'transparent',
                                          'height' => '100px',
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
                              array(
                                "type" => "spacer",
                                "styles" => array(
                                  "block" => array(
                                    "backgroundColor" => "transparent",
                                    "height" => "20px",
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
                      'display' => 'scale',
                      'src' => NULL,
                    ),
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#9bd2e0',
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
                              'display' => 'scale',
                              'src' => NULL,
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
                                  'text' => '<h2 style="text-align: center;"><span style="color: #4e4e4e;"><strong>Don\'t worry, we saved it for you...</strong></span></h2>',
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
                      'display' => 'scale',
                      'src' => NULL,
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
                              'display' => 'scale',
                              'src' => NULL,
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
              7 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                    array (
                      'display' => 'scale',
                      'src' => NULL,
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
                              'display' => 'scale',
                              'src' => NULL,
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
                                  'src' => $this->template_image_url . '/Kids-Clothing-Image.jpg',
                                  'alt' => 'Kids-Clothing-Image',
                                  'fullWidth' => false,
                                  'width' => '500px',
                                  'height' => '500px',
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
                          'orientation' => 'vertical',
                          'image' =>
                            array (
                              'display' => 'scale',
                              'src' => NULL,
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
                                          'height' => '25px',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<p><strong>Kids Dinosaur Suit</strong></p>
<p><span>$14.99</span></p>
<p></p>',
                                ),
                              2 =>
                                array (
                                  'type' => 'divider',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => 'transparent',
                                          'borderColor' => '#aaaaaa',
                                          'borderStyle' => 'solid',
                                          'borderWidth' => '3px',
                                          'padding' => '0px',
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
                              4 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<p style="font-size: 12px;">Size: <em>Small</em></p>
<p style="font-size: 12px;">Colour: <em>Varied</em></p>',
                                ),
                            ),
                        ),
                      2 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                            array (
                              'display' => 'scale',
                              'src' => NULL,
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
                                          'height' => '28px',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'Go To Cart',
                                  'url' => '',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#9bd2e0',
                                          'borderColor' => '#0074a2',
                                          'borderRadius' => '40px',
                                          'borderStyle' => 'solid',
                                          'borderWidth' => '0px',
                                          'fontColor' => '#4e4e4e',
                                          'fontFamily' => 'Arial',
                                          'fontSize' => '18px',
                                          'fontWeight' => 'bold',
                                          'lineHeight' => '40px',
                                          'textAlign' => 'left',
                                          'width' => '154px',
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
                      'display' => 'scale',
                      'src' => NULL,
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
                              'display' => 'scale',
                              'src' => NULL,
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
              9 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                    array (
                      'display' => 'scale',
                      'src' => NULL,
                    ),
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#fceba5',
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
                              'display' => 'scale',
                              'src' => NULL,
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
                                  'type' => 'text',
                                  'text' => '<h2 style="text-align: center;"><strong>YOU MIGHT ALSO LIKE...</strong></h2>',
                                ),
                            ),
                        ),
                    ),
                ),
              10 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                    array (
                      'display' => 'scale',
                      'src' => NULL,
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
                              'display' => 'scale',
                              'src' => NULL,
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
              11 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                    array (
                      'display' => 'scale',
                      'src' => NULL,
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
                              'display' => 'scale',
                              'src' => NULL,
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
                                  'src' => $this->template_image_url . '/Kids-Clothing-Image-3.jpg',
                                  'alt' => 'Kids-Clothing-Image-3',
                                  'fullWidth' => false,
                                  'width' => '500px',
                                  'height' => '500px',
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
                                  'text' => '<p><strong>Cherry Dress</strong></p>
<p><span>$10.99</span></p>',
                                ),
                              2 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'View',
                                  'url' => '',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#9bd2e0',
                                          'borderColor' => '#0074a2',
                                          'borderRadius' => '40px',
                                          'borderStyle' => 'solid',
                                          'borderWidth' => '0px',
                                          'fontColor' => '#4e4e4e',
                                          'fontFamily' => 'Arial',
                                          'fontSize' => '18px',
                                          'fontWeight' => 'bold',
                                          'lineHeight' => '40px',
                                          'textAlign' => 'left',
                                          'width' => '90px',
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
                              'display' => 'scale',
                              'src' => NULL,
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
                                  'src' => $this->template_image_url . '/Kids-Clothing-Image-2.jpg',
                                  'alt' => 'Kids-Clothing-Image-2',
                                  'fullWidth' => false,
                                  'width' => '500px',
                                  'height' => '500px',
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
                                  'text' => '<p><strong>Red T-Shirt</strong></p>
<p><span>$9.49</span></p>',
                                ),
                              2 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'View',
                                  'url' => '',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#9bd2e0',
                                          'borderColor' => '#0074a2',
                                          'borderRadius' => '40px',
                                          'borderStyle' => 'solid',
                                          'borderWidth' => '0px',
                                          'fontColor' => '#4e4e4e',
                                          'fontFamily' => 'Arial',
                                          'fontSize' => '18px',
                                          'fontWeight' => 'bold',
                                          'lineHeight' => '40px',
                                          'textAlign' => 'left',
                                          'width' => '90px',
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
                              'display' => 'scale',
                              'src' => NULL,
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
                                  'src' => $this->template_image_url . '/Kids-Clothing-Image-4.jpg',
                                  'alt' => 'Kids-Clothing-Image-4',
                                  'fullWidth' => false,
                                  'width' => '500px',
                                  'height' => '500px',
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
                                  'text' => '<p><strong>Pink Dance Dress</strong></p>
<p><span>$11.99</span></p>',
                                ),
                              2 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'View',
                                  'url' => '',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#9bd2e0',
                                          'borderColor' => '#0074a2',
                                          'borderRadius' => '40px',
                                          'borderStyle' => 'solid',
                                          'borderWidth' => '0px',
                                          'fontColor' => '#4e4e4e',
                                          'fontFamily' => 'Arial',
                                          'fontSize' => '18px',
                                          'fontWeight' => 'bold',
                                          'lineHeight' => '40px',
                                          'textAlign' => 'left',
                                          'width' => '90px',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
              12 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                    array (
                      'display' => 'scale',
                      'src' => NULL,
                    ),
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#f8f8f8',
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
                              'display' => 'scale',
                              'src' => NULL,
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
                                  'src' => $this->template_image_url . '/Kids-Clothing-Footer.jpg',
                                  'alt' => 'Kids-Clothing-Footer',
                                  'fullWidth' => true,
                                  'width' => '1280px',
                                  'height' => '107px',
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
              13 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                    array (
                      'display' => 'scale',
                      'src' => NULL,
                    ),
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#c3e1e8',
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
                              'display' => 'scale',
                              'src' => NULL,
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
                                  'src' => $this->template_image_url . '/Kids-Clothing-Logo-Footer-150x61.png',
                                  'alt' => 'Kids-Clothing-Logo-Footer',
                                  'fullWidth' => false,
                                  'width' => '150px',
                                  'height' => '61px',
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
                                  'type' => 'footer',
                                  'text' => '<p><strong><span style="color: #333333;"><a href="[link:subscription_unsubscribe_url]" style="color: #333333;">Unsubscribe</a> | <a href="[link:subscription_manage_url]" style="color: #333333;">Manage subscription</a></span></strong><br />Add your postal address here!</p>',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => 'transparent',
                                        ),
                                      'link' =>
                                        array (
                                          'fontColor' => '#6cb7d4',
                                          'textDecoration' => 'none',
                                        ),
                                      'text' =>
                                        array (
                                          'fontColor' => '#222222',
                                          'fontFamily' => 'Arial',
                                          'fontSize' => '12px',
                                          'textAlign' => 'center',
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
              'fontColor' => '#4e4e4e',
              'fontFamily' => 'Arial',
              'fontSize' => '16px',
            ),
          'h1' =>
            array (
              'fontColor' => '#4e4e4e',
              'fontFamily' => 'Arial',
              'fontSize' => '40px',
            ),
          'h2' =>
            array (
              'fontColor' => '#4e4e4e',
              'fontFamily' => 'Arial',
              'fontSize' => '24px',
            ),
          'h3' =>
            array (
              'fontColor' => '#4e4e4e',
              'fontFamily' => 'Arial',
              'fontSize' => '26px',
            ),
          'link' =>
            array (
              'fontColor' => '#9bd2e0',
              'textDecoration' => 'underline',
            ),
          'wrapper' =>
            array (
              'backgroundColor' => '#ffffff',
            ),
          'body' =>
            array (
              'backgroundColor' => '#c3e1e8',
            ),
        ),
      'blockDefaults' =>
        array (
          'automatedLatestContent' =>
            array (
              'amount' => '5',
              'authorPrecededBy' => 'Author:',
              'backgroundColor' => '#ffffff',
              'backgroundColorAlternate' => '#eeeeee',
              'categoriesPrecededBy' => 'Categories:',
              'contentType' => 'post',
              'displayType' => 'excerpt',
              'divider' =>
                array (
                  'context' => 'automatedLatestContent.divider',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => 'transparent',
                          'borderColor' => '#aaaaaa',
                          'borderStyle' => 'solid',
                          'borderWidth' => '3px',
                          'padding' => '13px',
                        ),
                    ),
                ),
              'featuredImagePosition' => 'belowTitle',
              'imageFullWidth' => false,
              'inclusionType' => 'include',
              'readMoreButton' =>
                array (
                  'context' => 'automatedLatestContent.readMoreButton',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#2ea1cd',
                          'borderColor' => '#0074a2',
                          'borderRadius' => '5px',
                          'borderStyle' => 'solid',
                          'borderWidth' => '1px',
                          'fontColor' => '#ffffff',
                          'fontFamily' => 'Verdana',
                          'fontSize' => '18px',
                          'fontWeight' => 'normal',
                          'lineHeight' => '40px',
                          'textAlign' => 'center',
                          'width' => '180px',
                        ),
                    ),
                  'text' => 'Read more',
                  'url' => '[postLink]',
                ),
              'readMoreText' => 'Read more',
              'readMoreType' => 'button',
              'showAuthor' => 'no',
              'showCategories' => 'no',
              'showDivider' => true,
              'sortBy' => 'newest',
              'titleAlignment' => 'left',
              'titleFormat' => 'h1',
              'titleIsLink' => false,
            ),
          'automatedLatestContentLayout' =>
            array (
              'amount' => '5',
              'authorPrecededBy' => 'Author:',
              'backgroundColor' => '#ffffff',
              'backgroundColorAlternate' => '#eeeeee',
              'categoriesPrecededBy' => 'Categories:',
              'contentType' => 'post',
              'displayType' => 'excerpt',
              'divider' =>
                array (
                  'context' => 'automatedLatestContentLayout.divider',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => 'transparent',
                          'borderColor' => '#aaaaaa',
                          'borderStyle' => 'solid',
                          'borderWidth' => '3px',
                          'padding' => '13px',
                        ),
                    ),
                ),
              'featuredImagePosition' => 'alternate',
              'imageFullWidth' => false,
              'inclusionType' => 'include',
              'readMoreButton' =>
                array (
                  'context' => 'automatedLatestContentLayout.readMoreButton',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#2ea1cd',
                          'borderColor' => '#0074a2',
                          'borderRadius' => '5px',
                          'borderStyle' => 'solid',
                          'borderWidth' => '1px',
                          'fontColor' => '#ffffff',
                          'fontFamily' => 'Verdana',
                          'fontSize' => '18px',
                          'fontWeight' => 'normal',
                          'lineHeight' => '40px',
                          'textAlign' => 'center',
                          'width' => '180px',
                        ),
                    ),
                  'text' => 'Read more',
                  'url' => '[postLink]',
                ),
              'readMoreText' => 'Read more',
              'readMoreType' => 'button',
              'showAuthor' => 'no',
              'showCategories' => 'no',
              'showDivider' => true,
              'sortBy' => 'newest',
              'titleAlignment' => 'left',
              'titleFormat' => 'h1',
              'titleIsLink' => false,
              'withLayout' => true,
            ),
          'button' =>
            array (
              'styles' =>
                array (
                  'block' =>
                    array (
                      'backgroundColor' => '#2ea1cd',
                      'borderColor' => '#0074a2',
                      'borderRadius' => '5px',
                      'borderStyle' => 'solid',
                      'borderWidth' => '1px',
                      'fontColor' => '#ffffff',
                      'fontFamily' => 'Verdana',
                      'fontSize' => '18px',
                      'fontWeight' => 'normal',
                      'lineHeight' => '40px',
                      'textAlign' => 'center',
                      'width' => '180px',
                    ),
                ),
              'text' => 'Button',
              'url' => '',
            ),
          'divider' =>
            array (
              'styles' =>
                array (
                  'block' =>
                    array (
                      'backgroundColor' => 'transparent',
                      'borderColor' => '#aaaaaa',
                      'borderStyle' => 'solid',
                      'borderWidth' => '3px',
                      'padding' => '13px',
                    ),
                ),
            ),
          'footer' =>
            array (
              'styles' =>
                array (
                  'block' =>
                    array (
                      'backgroundColor' => 'transparent',
                    ),
                  'link' =>
                    array (
                      'fontColor' => '#6cb7d4',
                      'textDecoration' => 'none',
                    ),
                  'text' =>
                    array (
                      'fontColor' => '#222222',
                      'fontFamily' => 'Arial',
                      'fontSize' => '12px',
                      'textAlign' => 'center',
                    ),
                ),
              'text' => '<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage subscription</a><br />Add your postal address here!</p>',
            ),
          'posts' =>
            array (
              'amount' => '10',
              'authorPrecededBy' => 'Author:',
              'backgroundColor' => '#ffffff',
              'backgroundColorAlternate' => '#eeeeee',
              'categoriesPrecededBy' => 'Categories:',
              'contentType' => 'post',
              'displayType' => 'excerpt',
              'divider' =>
                array (
                  'context' => 'posts.divider',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => 'transparent',
                          'borderColor' => '#aaaaaa',
                          'borderStyle' => 'solid',
                          'borderWidth' => '3px',
                          'padding' => '13px',
                        ),
                    ),
                ),
              'featuredImagePosition' => 'belowTitle',
              'imageFullWidth' => false,
              'inclusionType' => 'include',
              'postStatus' => 'publish',
              'readMoreButton' =>
                array (
                  'context' => 'posts.readMoreButton',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#2ea1cd',
                          'borderColor' => '#0074a2',
                          'borderRadius' => '5px',
                          'borderStyle' => 'solid',
                          'borderWidth' => '1px',
                          'fontColor' => '#ffffff',
                          'fontFamily' => 'Verdana',
                          'fontSize' => '18px',
                          'fontWeight' => 'normal',
                          'lineHeight' => '40px',
                          'textAlign' => 'center',
                          'width' => '180px',
                        ),
                    ),
                  'text' => 'Read more',
                  'url' => '[postLink]',
                ),
              'readMoreText' => 'Read more',
              'readMoreType' => 'link',
              'showAuthor' => 'no',
              'showCategories' => 'no',
              'showDivider' => true,
              'sortBy' => 'newest',
              'titleAlignment' => 'left',
              'titleFormat' => 'h1',
              'titleIsLink' => false,
            ),
          'social' =>
            array (
              'iconSet' => 'default',
              'icons' =>
                array (
                  0 =>
                    array (
                      'height' => '32px',
                      'iconType' => 'facebook',
                      'image' => $this->social_icon_url . '/01-social/Facebook.png?mailpoet_version=3.7.1',
                      'link' => 'http://www.facebook.com',
                      'text' => 'Facebook',
                      'type' => 'socialIcon',
                      'width' => '32px',
                    ),
                  1 =>
                    array (
                      'height' => '32px',
                      'iconType' => 'twitter',
                      'image' => $this->social_icon_url . '/01-social/Twitter.png?mailpoet_version=3.7.1',
                      'link' => 'http://www.twitter.com',
                      'text' => 'Twitter',
                      'type' => 'socialIcon',
                      'width' => '32px',
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
                      'height' => '80px',
                    ),
                ),
              'type' => 'spacer',
            ),
          'header' =>
            array (
              'styles' =>
                array (
                  'block' =>
                    array (
                      'backgroundColor' => 'transparent',
                    ),
                  'link' =>
                    array (
                      'fontColor' => '#6cb7d4',
                      'textDecoration' => 'underline',
                    ),
                  'text' =>
                    array (
                      'fontColor' => '#222222',
                      'fontFamily' => 'Arial',
                      'fontSize' => '12px',
                      'textAlign' => 'center',
                    ),
                ),
              'text' => 'Display problems?&nbsp;<a href="[link:newsletter_view_in_browser_url]">Open this email in your web browser.</a>',
            ),
        ),
    );
  }

}
