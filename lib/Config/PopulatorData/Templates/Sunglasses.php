<?php

namespace MailPoet\Config\PopulatorData\Templates;

class Sunglasses {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/sunglasses';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Sunglasses", 'mailpoet'),
      'categories' => json_encode(array('welcome', 'all')),
      'readonly' => 1,
      'thumbnail' => $this->getThumbnail(),
      'body' => json_encode($this->getBody()),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/sunglasses.jpg';
  }

  private function getBody() {
    return array (
      'content' =>
        array (
          'type' => 'container',
          'orientation' => 'vertical',
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
                              1 =>
                                array (
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Glasses-Logo.jpg',
                                  'alt' => 'Glasses-Logo',
                                  'fullWidth' => false,
                                  'width' => '250px',
                                  'height' => '66px',
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
                                  'type' => 'divider',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#ffffff',
                                          'padding' => '17px',
                                          'borderStyle' => 'solid',
                                          'borderWidth' => '2px',
                                          'borderColor' => '#f8b849',
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
                                          'height' => '30px',
                                        ),
                                    ),
                                ),
                              4 =>
                                array (
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Glasses-Header-2.jpg',
                                  'alt' => 'Glasses-Header-2',
                                  'fullWidth' => true,
                                  'width' => '1280px',
                                  'height' => '116px',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                              5 =>
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
              1 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
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
                                  'text' => '<h3 style="text-align: center;"><span style="color: #333333;"><strong>Here\'s what we sent you</strong></span></h3>',
                                ),
                            ),
                        ),
                    ),
                ),
              2 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
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
                                  'src' => $this->template_image_url . '/Glasses-Images-1.jpg',
                                  'alt' => 'Glasses-Images-1',
                                  'fullWidth' => true,
                                  'width' => '1280px',
                                  'height' => '650px',
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
                                  'text' => '<p style="text-align: center;"><span>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur nec nisi quis ex pulvinar molestie. Sed pulvinar placerat justo eu viverra.</span></p>',
                                ),
                              2 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'Choose These Frames',
                                  'url' => '',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#f8b849',
                                          'borderColor' => '#0074a2',
                                          'borderWidth' => '0px',
                                          'borderRadius' => '0px',
                                          'borderStyle' => 'solid',
                                          'width' => '195px',
                                          'lineHeight' => '40px',
                                          'fontColor' => '#ffffff',
                                          'fontFamily' => 'Arial',
                                          'fontSize' => '16px',
                                          'fontWeight' => 'normal',
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                              3 =>
                                array (
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Glasses-Images-2.jpg',
                                  'alt' => 'Glasses-Images-2',
                                  'fullWidth' => true,
                                  'width' => '1280px',
                                  'height' => '650px',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                              4 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<p style="text-align: center;"><span>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur nec nisi quis ex pulvinar molestie. Sed pulvinar placerat justo eu viverra.</span></p>',
                                ),
                              5 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'Choose These Frames',
                                  'url' => '',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#f8b849',
                                          'borderColor' => '#0074a2',
                                          'borderWidth' => '0px',
                                          'borderRadius' => '0px',
                                          'borderStyle' => 'solid',
                                          'width' => '195px',
                                          'lineHeight' => '40px',
                                          'fontColor' => '#ffffff',
                                          'fontFamily' => 'Arial',
                                          'fontSize' => '16px',
                                          'fontWeight' => 'normal',
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                              6 =>
                                array (
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Glasses-Images-3.jpg',
                                  'alt' => 'Glasses-Images-3',
                                  'fullWidth' => true,
                                  'width' => '1280px',
                                  'height' => '650px',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                              7 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<p style="text-align: center;"><span>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur nec nisi quis ex pulvinar molestie. Sed pulvinar placerat justo eu viverra.</span></p>',
                                ),
                              8 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'Choose These Frames',
                                  'url' => '',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#f8b849',
                                          'borderColor' => '#0074a2',
                                          'borderWidth' => '0px',
                                          'borderRadius' => '0px',
                                          'borderStyle' => 'solid',
                                          'width' => '195px',
                                          'lineHeight' => '40px',
                                          'fontColor' => '#ffffff',
                                          'fontFamily' => 'Arial',
                                          'fontSize' => '16px',
                                          'fontWeight' => 'normal',
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                              9 =>
                                array (
                                  'type' => 'divider',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#ffffff',
                                          'padding' => '34.5px',
                                          'borderStyle' => 'solid',
                                          'borderWidth' => '2px',
                                          'borderColor' => '#f8b849',
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
                                  'src' => $this->template_image_url . '/Glasses-Header.jpg',
                                  'alt' => 'Glasses-Header',
                                  'fullWidth' => true,
                                  'width' => '640px',
                                  'height' => '920px',
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
                                          'height' => '60px',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<h3><strong>Our Summer Range Is Here</strong></h3>
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur nec nisi quis ex pulvinar molestie. Sed pulvinar placerat justo eu viverra. Pellentesque in interdum eros, a venenatis velit.</p>
<p></p>
<p>Fusce finibus convallis augue, ut viverra felis placerat in. Curabitur et commodo ipsum. Mauris tellus metus, tristique vel sollicitudin ut, malesuada in augue.&nbsp;</p>',
                                ),
                              2 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'Find Out More',
                                  'url' => '',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#f8b849',
                                          'borderColor' => '#0074a2',
                                          'borderWidth' => '0px',
                                          'borderRadius' => '0px',
                                          'borderStyle' => 'solid',
                                          'width' => '137px',
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
                    ),
                ),
              4 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
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
                                  'type' => 'divider',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#ffffff',
                                          'padding' => '34.5px',
                                          'borderStyle' => 'solid',
                                          'borderWidth' => '2px',
                                          'borderColor' => '#f8b849',
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
                                  'text' => '<h2 style="text-align: center;"><span style="color: #333333;"><strong>Got any questions or need some help?</strong></span></h2>
<p style="text-align: center;"><span style="color: #333333;">We\'re just a click or a phone call away.</span></p>
<p style="text-align: center;"><span style="color: #333333;"></span></p>
<h3 style="text-align: center;"><span style="color: #333333;"><strong>Call Us:</strong> 08856877854</span></h3>
<h3 style="text-align: center;"></h3>',
                                ),
                              1 =>
                                array (
                                  'type' => 'divider',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#ffffff',
                                          'padding' => '23.5px',
                                          'borderStyle' => 'solid',
                                          'borderWidth' => '2px',
                                          'borderColor' => '#f8b849',
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
                                  'type' => 'social',
                                  'iconSet' => 'full-symbol-black',
                                  'icons' =>
                                    array (
                                      0 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'facebook',
                                          'link' => 'http://www.facebook.com',
                                          'image' => $this->social_icon_url . '/07-full-symbol-black/Facebook.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Facebook',
                                        ),
                                      1 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'twitter',
                                          'link' => 'http://www.twitter.com',
                                          'image' => $this->social_icon_url . '/07-full-symbol-black/Twitter.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Twitter',
                                        ),
                                      2 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'instagram',
                                          'link' => 'http://instagram.com',
                                          'image' => $this->social_icon_url . '/07-full-symbol-black/Instagram.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Instagram',
                                        ),
                                      3 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'email',
                                          'link' => '',
                                          'image' => $this->social_icon_url . '/07-full-symbol-black/Email.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Email',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<p style="text-align: center; font-size: 11px;"><strong><span style="color: #808080;"><a href="[link:subscription_unsubscribe_url]" style="color: #808080;">Unsubscribe</a>&nbsp;|&nbsp;<a href="[link:subscription_manage_url]" style="color: #808080;">Manage your subscription</a></span></strong><br /><span style="color: #808080;">Add your postal address here!</span></p>',
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
              'fontColor' => '#21759B',
              'textDecoration' => 'underline',
            ),
          'wrapper' =>
            array (
              'backgroundColor' => '#ffffff',
            ),
          'body' =>
            array (
              'backgroundColor' => '#ffffff',
            ),
        ),
    );
  }


}
