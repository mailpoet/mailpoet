<?php
namespace MailPoet\Config\PopulatorData\Templates;

if(!defined('ABSPATH')) exit;

class GiftWelcome {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/gift';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Gift Welcome", 'mailpoet'),
      'categories' => json_encode(array('welcome', 'all')),
      'readonly' => 1,
      'thumbnail' => $this->getThumbnail(),
      'body' => json_encode($this->getBody()),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/gift.jpg';
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
                                  'src' => $this->template_image_url . '/Gift-Header-1.jpg',
                                  'alt' => 'Gift-Header-1',
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
                          'backgroundColor' => '#e7e7e7',
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
                                  'text' => '<h2 style="text-align: center;"><span style="color: #dd2d2d;">We\'re so happy you\'re onboard!</span></h2>
<p style="text-align: center;"><span style="color: #333333;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur nec nisi quis ex pulvinar molestie. Sed pulvinar placerat justo eu viverra. Pellentesque in interdum eros, a venenatis velit. Fusce finibus convallis augue, ut viverra felis placerat in. </span></p>
<p style="text-align: center;"><span style="color: #333333;"></span></p>
<p style="text-align: center;"><span style="color: #333333;">Curabitur et commodo ipsum. Mauris tellus metus, tristique vel sollicitudin ut, malesuada in augue. Aliquam ultricies purus vel commodo vehicula.</span></p>',
                                ),
                              1 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'Get Started',
                                  'url' => '',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#dd2d2d',
                                          'borderColor' => '#0074a2',
                                          'borderWidth' => '0px',
                                          'borderRadius' => '40px',
                                          'borderStyle' => 'solid',
                                          'width' => '180px',
                                          'lineHeight' => '50px',
                                          'fontColor' => '#ffffff',
                                          'fontFamily' => 'Arial',
                                          'fontSize' => '22px',
                                          'fontWeight' => 'bold',
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                              2 =>
                                array (
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Gift-Footer.jpg',
                                  'alt' => 'Gift-Footer',
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
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => 'transparent',
                                          'height' => '23px',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<p style="font-size: 11px; text-align: center;"><span style="color: #808080;"><strong>Address Line 1</strong></span></p>
<p style="font-size: 11px; text-align: center;"><span style="color: #808080;"><strong>Address Line 2</strong></span></p>
<p style="font-size: 11px; text-align: center;"><span style="color: #808080;"><strong>City</strong></span></p>
<p style="font-size: 11px; text-align: center;"><span style="color: #808080;"><strong>Country</strong></span></p>',
                                ),
                              2 =>
                                array (
                                  'type' => 'divider',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => 'transparent',
                                          'padding' => '5.5px',
                                          'borderStyle' => 'solid',
                                          'borderWidth' => '1px',
                                          'borderColor' => '#aaaaaa',
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
                                  'type' => 'social',
                                  'iconSet' => 'grey',
                                  'icons' =>
                                    array (
                                      0 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'facebook',
                                          'link' => 'http://www.facebook.com',
                                          'image' => $this->social_icon_url . '/02-grey/Facebook.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Facebook',
                                        ),
                                      1 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'twitter',
                                          'link' => 'http://www.twitter.com',
                                          'image' => $this->social_icon_url . '/02-grey/Twitter.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Twitter',
                                        ),
                                      2 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'instagram',
                                          'link' => 'http://instagram.com',
                                          'image' => $this->social_icon_url . '/02-grey/Instagram.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Instagram',
                                        ),
                                    ),
                                ),
                              5 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<p style="text-align: center; font-size: 11px;"><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a><span>&nbsp;|&nbsp;</span><a href="[link:subscription_manage_url]">Manage your subscription</a></p>',
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
              'fontColor' => '#dd2d2d',
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
