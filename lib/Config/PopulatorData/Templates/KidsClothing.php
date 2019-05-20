<?php
namespace MailPoet\Config\PopulatorData\Templates;

use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;


class KidsClothing {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/kids-clothing';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return [
      'name' => WPFunctions::get()->__("Kids Clothing", 'mailpoet'),
      'categories' => json_encode(['woocommerce', 'all']),
      'readonly' => 1,
      'thumbnail' => $this->getThumbnail(),
      'body' => json_encode($this->getBody()),
    ];
  }

  private function getThumbnail() {
    return $this->template_image_url . '/thumbnail.20190411-1500.jpg';
  }

  private function getBody() {
    return  [
      'content' =>
         [
          'type' => 'container',
          'orientation' => 'vertical',
          'image' =>
             [
              'display' => 'scale',
              'src' => null,
             ],
          'styles' =>
             [
              'block' =>
                 [
                  'backgroundColor' => 'transparent',
                 ],
             ],
          'blocks' =>
             [
              0 =>
                 [
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                     [
                      'display' => 'scale',
                      'src' => null,
                     ],
                  'styles' =>
                     [
                      'block' =>
                         [
                          'backgroundColor' => '#c3e1e8',
                         ],
                     ],
                  'blocks' =>
                     [
                      0 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'spacer',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'backgroundColor' => 'transparent',
                                          'height' => '20px',
                                         ],
                                     ],
                                 ],
                             ],
                         ],
                     ],
                 ],
              1 =>
                 [
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                     [
                      'display' => 'scale',
                      'src' => null,
                     ],
                  'styles' =>
                     [
                      'block' =>
                         [
                          'backgroundColor' => '#c3e1e8',
                         ],
                     ],
                  'blocks' =>
                     [
                      0 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'spacer',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'backgroundColor' => 'transparent',
                                          'height' => '40px',
                                         ],
                                     ],
                                 ],
                              1 =>
                                 [
                                  'type' => 'social',
                                  'iconSet' => 'circles',
                                  'icons' =>
                                     [
                                      0 =>
                                         [
                                          'type' => 'socialIcon',
                                          'iconType' => 'facebook',
                                          'link' => 'http://www.facebook.com',
                                          'image' => $this->social_icon_url . '/03-circles/Facebook.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Facebook',
                                         ],
                                      1 =>
                                         [
                                          'type' => 'socialIcon',
                                          'iconType' => 'twitter',
                                          'link' => 'http://www.twitter.com',
                                          'image' => $this->social_icon_url . '/03-circles/Twitter.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Twitter',
                                         ],
                                     ],
                                 ],
                             ],
                         ],
                      1 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Kids-Clothing-Logo.png',
                                  'alt' => 'Kids-Clothing-Logo',
                                  'fullWidth' => true,
                                  'width' => '250px',
                                  'height' => '121px',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'textAlign' => 'center',
                                         ],
                                     ],
                                 ],
                             ],
                         ],
                      2 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'spacer',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'backgroundColor' => 'transparent',
                                          'height' => '40px',
                                         ],
                                     ],
                                 ],
                              1 =>
                                 [
                                  'type' => 'social',
                                  'iconSet' => 'circles',
                                  'icons' =>
                                     [
                                      0 =>
                                         [
                                          'type' => 'socialIcon',
                                          'iconType' => 'google-plus',
                                          'link' => 'http://plus.google.com',
                                          'image' => $this->social_icon_url . '/03-circles/Google-Plus.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Google Plus',
                                         ],
                                      1 =>
                                         [
                                          'type' => 'socialIcon',
                                          'iconType' => 'instagram',
                                          'link' => 'http://instagram.com',
                                          'image' => $this->social_icon_url . '/03-circles/Instagram.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Instagram',
                                         ],
                                     ],
                                 ],
                             ],
                         ],
                     ],
                 ],
              2 =>
                 [
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                     [
                      'display' => 'scale',
                      'src' => null,
                     ],
                  'styles' =>
                     [
                      'block' =>
                         [
                          'backgroundColor' => '#9bd2e0',
                         ],
                     ],
                  'blocks' =>
                     [
                      0 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'spacer',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'backgroundColor' => 'transparent',
                                          'height' => '20px',
                                         ],
                                     ],
                                 ],
                             ],
                         ],
                     ],
                 ],
              3 =>
                 [
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                     [
                      'display' => 'scale',
                      'src' => null,
                     ],
                  'styles' =>
                     [
                      'block' =>
                         [
                          'backgroundColor' => '#9bd2e0',
                         ],
                     ],
                  'blocks' =>
                     [
                      0 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'text',
                                  'text' => '<p style="text-align: center;"><span style="color: #4e4e4e;"><strong>Boys Clothes</strong></span></p>',
                                 ],
                             ],
                         ],
                      1 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'text',
                                  'text' => '<p style="text-align: center;"><span style="color: #4e4e4e;"><strong>Girls Clothes</strong></span></p>',
                                 ],
                             ],
                         ],
                      2 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'text',
                                  'text' => '<p style="text-align: center;"><span style="color: #4e4e4e;"><strong>Toys &amp; Games</strong></span></p>',
                                 ],
                             ],
                         ],
                     ],
                 ],
              4 =>
                 [
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                     [
                      'src' => $this->template_image_url . '/Kids-Clothing-Header.jpg',
                      'display' => 'scale',
                     ],
                  'styles' =>
                     [
                      'block' =>
                         [
                          'backgroundColor' => '#9cd1e1',
                         ],
                     ],
                  'blocks' =>
                     [
                      0 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'src' => null,
                              'display' => 'scale',
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'spacer',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'backgroundColor' => 'transparent',
                                          'height' => '80px',
                                         ],
                                     ],
                                 ],
                              1 =>
                                 [
                                  'type' => 'text',
                                  'text' => '<h1><strong>Wait!</strong></h1>
<h3>You\'ve left something in your cart!</h3>',
                                 ],
                              2 =>
                                 [
                                  'type' => 'spacer',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'backgroundColor' => 'transparent',
                                          'height' => '100px',
                                         ],
                                     ],
                                 ],
                             ],
                         ],
                      1 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'src' => null,
                              'display' => 'scale',
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              [
                                "type" => "spacer",
                                "styles" => [
                                  "block" => [
                                    "backgroundColor" => "transparent",
                                    "height" => "20px",
                                  ],
                                ],
                              ],
                             ],
                         ],
                     ],
                 ],
              5 =>
                 [
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                     [
                      'display' => 'scale',
                      'src' => null,
                     ],
                  'styles' =>
                     [
                      'block' =>
                         [
                          'backgroundColor' => '#9bd2e0',
                         ],
                     ],
                  'blocks' =>
                     [
                      0 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'text',
                                  'text' => '<h2 style="text-align: center;"><span style="color: #4e4e4e;"><strong>Don\'t worry, we saved it for you...</strong></span></h2>',
                                 ],
                             ],
                         ],
                     ],
                 ],
              6 =>
                 [
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                     [
                      'display' => 'scale',
                      'src' => null,
                     ],
                  'styles' =>
                     [
                      'block' =>
                         [
                          'backgroundColor' => 'transparent',
                         ],
                     ],
                  'blocks' =>
                     [
                      0 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'spacer',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'backgroundColor' => 'transparent',
                                          'height' => '40px',
                                         ],
                                     ],
                                 ],
                             ],
                         ],
                     ],
                 ],
              7 =>
                 [
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                     [
                      'display' => 'scale',
                      'src' => null,
                     ],
                  'styles' =>
                     [
                      'block' =>
                         [
                          'backgroundColor' => 'transparent',
                         ],
                     ],
                  'blocks' =>
                     [
                      0 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Kids-Clothing-Image.jpg',
                                  'alt' => 'Kids-Clothing-Image',
                                  'fullWidth' => false,
                                  'width' => '500px',
                                  'height' => '500px',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'textAlign' => 'center',
                                         ],
                                     ],
                                 ],
                             ],
                         ],
                      1 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'spacer',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'backgroundColor' => 'transparent',
                                          'height' => '25px',
                                         ],
                                     ],
                                 ],
                              1 =>
                                 [
                                  'type' => 'text',
                                  'text' => '<p><strong>Kids Dinosaur Suit</strong></p>
<p><span>$14.99</span></p>
<p></p>',
                                 ],
                              2 =>
                                 [
                                  'type' => 'divider',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'backgroundColor' => 'transparent',
                                          'borderColor' => '#aaaaaa',
                                          'borderStyle' => 'solid',
                                          'borderWidth' => '3px',
                                          'padding' => '0px',
                                         ],
                                     ],
                                 ],
                              3 =>
                                 [
                                  'type' => 'spacer',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'backgroundColor' => 'transparent',
                                          'height' => '20px',
                                         ],
                                     ],
                                 ],
                              4 =>
                                 [
                                  'type' => 'text',
                                  'text' => '<p style="font-size: 12px;">Size: <em>Small</em></p>
<p style="font-size: 12px;">Colour: <em>Varied</em></p>',
                                 ],
                             ],
                         ],
                      2 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'spacer',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'backgroundColor' => 'transparent',
                                          'height' => '28px',
                                         ],
                                     ],
                                 ],
                              1 =>
                                 [
                                  'type' => 'button',
                                  'text' => 'Go To Cart',
                                  'url' => '',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
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
                                         ],
                                     ],
                                 ],
                             ],
                         ],
                     ],
                 ],
              8 =>
                 [
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                     [
                      'display' => 'scale',
                      'src' => null,
                     ],
                  'styles' =>
                     [
                      'block' =>
                         [
                          'backgroundColor' => 'transparent',
                         ],
                     ],
                  'blocks' =>
                     [
                      0 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'spacer',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'backgroundColor' => 'transparent',
                                          'height' => '20px',
                                         ],
                                     ],
                                 ],
                             ],
                         ],
                     ],
                 ],
              9 =>
                 [
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                     [
                      'display' => 'scale',
                      'src' => null,
                     ],
                  'styles' =>
                     [
                      'block' =>
                         [
                          'backgroundColor' => '#fceba5',
                         ],
                     ],
                  'blocks' =>
                     [
                      0 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'spacer',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'backgroundColor' => 'transparent',
                                          'height' => '20px',
                                         ],
                                     ],
                                 ],
                              1 =>
                                 [
                                  'type' => 'text',
                                  'text' => '<h2 style="text-align: center;"><strong>YOU MIGHT ALSO LIKE...</strong></h2>',
                                 ],
                             ],
                         ],
                     ],
                 ],
              10 =>
                 [
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                     [
                      'display' => 'scale',
                      'src' => null,
                     ],
                  'styles' =>
                     [
                      'block' =>
                         [
                          'backgroundColor' => 'transparent',
                         ],
                     ],
                  'blocks' =>
                     [
                      0 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'spacer',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'backgroundColor' => 'transparent',
                                          'height' => '40px',
                                         ],
                                     ],
                                 ],
                             ],
                         ],
                     ],
                 ],
              11 =>
                 [
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                     [
                      'display' => 'scale',
                      'src' => null,
                     ],
                  'styles' =>
                     [
                      'block' =>
                         [
                          'backgroundColor' => 'transparent',
                         ],
                     ],
                  'blocks' =>
                     [
                      0 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Kids-Clothing-Image-3.jpg',
                                  'alt' => 'Kids-Clothing-Image-3',
                                  'fullWidth' => false,
                                  'width' => '500px',
                                  'height' => '500px',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'textAlign' => 'center',
                                         ],
                                     ],
                                 ],
                              1 =>
                                 [
                                  'type' => 'text',
                                  'text' => '<p><strong>Cherry Dress</strong></p>
<p><span>$10.99</span></p>',
                                 ],
                              2 =>
                                 [
                                  'type' => 'button',
                                  'text' => 'View',
                                  'url' => '',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
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
                                         ],
                                     ],
                                 ],
                             ],
                         ],
                      1 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Kids-Clothing-Image-2.jpg',
                                  'alt' => 'Kids-Clothing-Image-2',
                                  'fullWidth' => false,
                                  'width' => '500px',
                                  'height' => '500px',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'textAlign' => 'center',
                                         ],
                                     ],
                                 ],
                              1 =>
                                 [
                                  'type' => 'text',
                                  'text' => '<p><strong>Red T-Shirt</strong></p>
<p><span>$9.49</span></p>',
                                 ],
                              2 =>
                                 [
                                  'type' => 'button',
                                  'text' => 'View',
                                  'url' => '',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
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
                                         ],
                                     ],
                                 ],
                             ],
                         ],
                      2 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Kids-Clothing-Image-4.jpg',
                                  'alt' => 'Kids-Clothing-Image-4',
                                  'fullWidth' => false,
                                  'width' => '500px',
                                  'height' => '500px',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'textAlign' => 'center',
                                         ],
                                     ],
                                 ],
                              1 =>
                                 [
                                  'type' => 'text',
                                  'text' => '<p><strong>Pink Dance Dress</strong></p>
<p><span>$11.99</span></p>',
                                 ],
                              2 =>
                                 [
                                  'type' => 'button',
                                  'text' => 'View',
                                  'url' => '',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
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
                                         ],
                                     ],
                                 ],
                             ],
                         ],
                     ],
                 ],
              12 =>
                 [
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                     [
                      'display' => 'scale',
                      'src' => null,
                     ],
                  'styles' =>
                     [
                      'block' =>
                         [
                          'backgroundColor' => '#f8f8f8',
                         ],
                     ],
                  'blocks' =>
                     [
                      0 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Kids-Clothing-Footer.jpg',
                                  'alt' => 'Kids-Clothing-Footer',
                                  'fullWidth' => true,
                                  'width' => '1280px',
                                  'height' => '107px',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'textAlign' => 'center',
                                         ],
                                     ],
                                 ],
                             ],
                         ],
                     ],
                 ],
              13 =>
                 [
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'image' =>
                     [
                      'display' => 'scale',
                      'src' => null,
                     ],
                  'styles' =>
                     [
                      'block' =>
                         [
                          'backgroundColor' => '#c3e1e8',
                         ],
                     ],
                  'blocks' =>
                     [
                      0 =>
                         [
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'image' =>
                             [
                              'display' => 'scale',
                              'src' => null,
                             ],
                          'styles' =>
                             [
                              'block' =>
                                 [
                                  'backgroundColor' => 'transparent',
                                 ],
                             ],
                          'blocks' =>
                             [
                              0 =>
                                 [
                                  'type' => 'spacer',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'backgroundColor' => 'transparent',
                                          'height' => '20px',
                                         ],
                                     ],
                                 ],
                              1 =>
                                 [
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Kids-Clothing-Logo-Footer-150x61.png',
                                  'alt' => 'Kids-Clothing-Logo-Footer',
                                  'fullWidth' => false,
                                  'width' => '150px',
                                  'height' => '61px',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'textAlign' => 'center',
                                         ],
                                     ],
                                 ],
                              2 =>
                                 [
                                  'type' => 'footer',
                                  'text' => '<p><strong><span style="color: #333333;"><a href="[link:subscription_unsubscribe_url]" style="color: #333333;">Unsubscribe</a> | <a href="[link:subscription_manage_url]" style="color: #333333;">Manage subscription</a></span></strong><br />Add your postal address here!</p>',
                                  'styles' =>
                                     [
                                      'block' =>
                                         [
                                          'backgroundColor' => 'transparent',
                                         ],
                                      'link' =>
                                         [
                                          'fontColor' => '#6cb7d4',
                                          'textDecoration' => 'none',
                                         ],
                                      'text' =>
                                         [
                                          'fontColor' => '#222222',
                                          'fontFamily' => 'Arial',
                                          'fontSize' => '12px',
                                          'textAlign' => 'center',
                                         ],
                                     ],
                                 ],
                             ],
                         ],
                     ],
                 ],
             ],
         ],
      'globalStyles' =>
         [
          'text' =>
             [
              'fontColor' => '#4e4e4e',
              'fontFamily' => 'Arial',
              'fontSize' => '16px',
             ],
          'h1' =>
             [
              'fontColor' => '#4e4e4e',
              'fontFamily' => 'Arial',
              'fontSize' => '40px',
             ],
          'h2' =>
             [
              'fontColor' => '#4e4e4e',
              'fontFamily' => 'Arial',
              'fontSize' => '24px',
             ],
          'h3' =>
             [
              'fontColor' => '#4e4e4e',
              'fontFamily' => 'Arial',
              'fontSize' => '26px',
             ],
          'link' =>
             [
              'fontColor' => '#9bd2e0',
              'textDecoration' => 'underline',
             ],
          'wrapper' =>
             [
              'backgroundColor' => '#ffffff',
             ],
          'body' =>
             [
              'backgroundColor' => '#c3e1e8',
             ],
         ],
      'blockDefaults' =>
         [
          'automatedLatestContent' =>
             [
              'amount' => '5',
              'authorPrecededBy' => 'Author:',
              'backgroundColor' => '#ffffff',
              'backgroundColorAlternate' => '#eeeeee',
              'categoriesPrecededBy' => 'Categories:',
              'contentType' => 'post',
              'displayType' => 'excerpt',
              'divider' =>
                 [
                  'context' => 'automatedLatestContent.divider',
                  'styles' =>
                     [
                      'block' =>
                         [
                          'backgroundColor' => 'transparent',
                          'borderColor' => '#aaaaaa',
                          'borderStyle' => 'solid',
                          'borderWidth' => '3px',
                          'padding' => '13px',
                         ],
                     ],
                 ],
              'featuredImagePosition' => 'belowTitle',
              'imageFullWidth' => false,
              'inclusionType' => 'include',
              'readMoreButton' =>
                 [
                  'context' => 'automatedLatestContent.readMoreButton',
                  'styles' =>
                     [
                      'block' =>
                         [
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
                         ],
                     ],
                  'text' => 'Read more',
                  'url' => '[postLink]',
                 ],
              'readMoreText' => 'Read more',
              'readMoreType' => 'button',
              'showAuthor' => 'no',
              'showCategories' => 'no',
              'showDivider' => true,
              'sortBy' => 'newest',
              'titleAlignment' => 'left',
              'titleFormat' => 'h1',
              'titleIsLink' => false,
             ],
          'automatedLatestContentLayout' =>
             [
              'amount' => '5',
              'authorPrecededBy' => 'Author:',
              'backgroundColor' => '#ffffff',
              'backgroundColorAlternate' => '#eeeeee',
              'categoriesPrecededBy' => 'Categories:',
              'contentType' => 'post',
              'displayType' => 'excerpt',
              'divider' =>
                 [
                  'context' => 'automatedLatestContentLayout.divider',
                  'styles' =>
                     [
                      'block' =>
                         [
                          'backgroundColor' => 'transparent',
                          'borderColor' => '#aaaaaa',
                          'borderStyle' => 'solid',
                          'borderWidth' => '3px',
                          'padding' => '13px',
                         ],
                     ],
                 ],
              'featuredImagePosition' => 'alternate',
              'imageFullWidth' => false,
              'inclusionType' => 'include',
              'readMoreButton' =>
                 [
                  'context' => 'automatedLatestContentLayout.readMoreButton',
                  'styles' =>
                     [
                      'block' =>
                         [
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
                         ],
                     ],
                  'text' => 'Read more',
                  'url' => '[postLink]',
                 ],
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
             ],
          'button' =>
             [
              'styles' =>
                 [
                  'block' =>
                     [
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
                     ],
                 ],
              'text' => 'Button',
              'url' => '',
             ],
          'divider' =>
             [
              'styles' =>
                 [
                  'block' =>
                     [
                      'backgroundColor' => 'transparent',
                      'borderColor' => '#aaaaaa',
                      'borderStyle' => 'solid',
                      'borderWidth' => '3px',
                      'padding' => '13px',
                     ],
                 ],
             ],
          'footer' =>
             [
              'styles' =>
                 [
                  'block' =>
                     [
                      'backgroundColor' => 'transparent',
                     ],
                  'link' =>
                     [
                      'fontColor' => '#6cb7d4',
                      'textDecoration' => 'none',
                     ],
                  'text' =>
                     [
                      'fontColor' => '#222222',
                      'fontFamily' => 'Arial',
                      'fontSize' => '12px',
                      'textAlign' => 'center',
                     ],
                 ],
              'text' => '<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage subscription</a><br />Add your postal address here!</p>',
             ],
          'posts' =>
             [
              'amount' => '10',
              'authorPrecededBy' => 'Author:',
              'backgroundColor' => '#ffffff',
              'backgroundColorAlternate' => '#eeeeee',
              'categoriesPrecededBy' => 'Categories:',
              'contentType' => 'post',
              'displayType' => 'excerpt',
              'divider' =>
                 [
                  'context' => 'posts.divider',
                  'styles' =>
                     [
                      'block' =>
                         [
                          'backgroundColor' => 'transparent',
                          'borderColor' => '#aaaaaa',
                          'borderStyle' => 'solid',
                          'borderWidth' => '3px',
                          'padding' => '13px',
                         ],
                     ],
                 ],
              'featuredImagePosition' => 'belowTitle',
              'imageFullWidth' => false,
              'inclusionType' => 'include',
              'postStatus' => 'publish',
              'readMoreButton' =>
                 [
                  'context' => 'posts.readMoreButton',
                  'styles' =>
                     [
                      'block' =>
                         [
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
                         ],
                     ],
                  'text' => 'Read more',
                  'url' => '[postLink]',
                 ],
              'readMoreText' => 'Read more',
              'readMoreType' => 'link',
              'showAuthor' => 'no',
              'showCategories' => 'no',
              'showDivider' => true,
              'sortBy' => 'newest',
              'titleAlignment' => 'left',
              'titleFormat' => 'h1',
              'titleIsLink' => false,
             ],
          'social' =>
             [
              'iconSet' => 'default',
              'icons' =>
                 [
                  0 =>
                     [
                      'height' => '32px',
                      'iconType' => 'facebook',
                      'image' => $this->social_icon_url . '/01-social/Facebook.png?mailpoet_version=3.7.1',
                      'link' => 'http://www.facebook.com',
                      'text' => 'Facebook',
                      'type' => 'socialIcon',
                      'width' => '32px',
                     ],
                  1 =>
                     [
                      'height' => '32px',
                      'iconType' => 'twitter',
                      'image' => $this->social_icon_url . '/01-social/Twitter.png?mailpoet_version=3.7.1',
                      'link' => 'http://www.twitter.com',
                      'text' => 'Twitter',
                      'type' => 'socialIcon',
                      'width' => '32px',
                     ],
                 ],
             ],
          'spacer' =>
             [
              'styles' =>
                 [
                  'block' =>
                     [
                      'backgroundColor' => 'transparent',
                      'height' => '80px',
                     ],
                 ],
              'type' => 'spacer',
             ],
          'header' =>
             [
              'styles' =>
                 [
                  'block' =>
                     [
                      'backgroundColor' => 'transparent',
                     ],
                  'link' =>
                     [
                      'fontColor' => '#6cb7d4',
                      'textDecoration' => 'underline',
                     ],
                  'text' =>
                     [
                      'fontColor' => '#222222',
                      'fontFamily' => 'Arial',
                      'fontSize' => '12px',
                      'textAlign' => 'center',
                     ],
                 ],
              'text' => 'Display problems?&nbsp;<a href="[link:newsletter_view_in_browser_url]">Open this email in your web browser.</a>',
             ],
         ],
    ];
  }

}
