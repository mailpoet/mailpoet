<?php
namespace MailPoet\Config\PopulatorData\Templates;

if(!defined('ABSPATH')) exit;

class FashionStore {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/fashion';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Fashion Store", 'mailpoet'),
      'categories' => json_encode(array('standard', 'all')),
      'readonly' => 1,
      'thumbnail' => $this->getThumbnail(),
      'body' => json_encode($this->getBody()),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/fashion.jpeg';
  }

  private function getBody() {
    return
      array(
        'content' =>
          array(
            'type' => 'container',
            'orientation' => 'vertical',
            'image' =>
              array(
                'src' => NULL,
                'display' => 'scale',
              ),
            'styles' =>
              array(
                'block' =>
                  array(
                    'backgroundColor' => 'transparent',
                  ),
              ),
            'blocks' =>
              array(
                0 =>
                  array(
                    'type' => 'container',
                    'orientation' => 'horizontal',
                    'image' =>
                      array(
                        'src' => NULL,
                        'display' => 'scale',
                      ),
                    'styles' =>
                      array(
                        'block' =>
                          array(
                            'backgroundColor' => 'transparent',
                          ),
                      ),
                    'blocks' =>
                      array(
                        0 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'spacer',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'backgroundColor' => 'transparent',
                                            'height' => '20px',
                                          ),
                                      ),
                                  ),
                                1 =>
                                  array(
                                    'type' => 'image',
                                    'link' => '',
                                    'src' => $this->template_image_url . '/tulip-2.png',
                                    'alt' => 'tulip',
                                    'fullWidth' => false,
                                    'width' => '26.5px',
                                    'height' => '64px',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'textAlign' => 'left',
                                          ),
                                      ),
                                  ),
                                2 =>
                                  array(
                                    'type' => 'text',
                                    'text' => '<h1><strong>TULIP PARK</strong></h1>',
                                  ),
                              ),
                          ),
                        1 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'spacer',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'backgroundColor' => 'transparent',
                                            'height' => '85px',
                                          ),
                                      ),
                                  ),
                                1 =>
                                  array(
                                    'type' => 'text',
                                    'text' => '<h3 style="text-align: right;"><span style="color: #bcbcbc;">Since 1987</span></h3>',
                                  ),
                              ),
                          ),
                      ),
                  ),
                1 =>
                  array(
                    'type' => 'container',
                    'orientation' => 'horizontal',
                    'image' =>
                      array(
                        'src' => $this->template_image_url . '/Fashion-Header.jpg',
                        'display' => 'scale',
                      ),
                    'styles' =>
                      array(
                        'block' =>
                          array(
                            'backgroundColor' => '#f8f8f8',
                          ),
                      ),
                    'blocks' =>
                      array(
                        0 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'spacer',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'backgroundColor' => 'transparent',
                                            'height' => '486px',
                                          ),
                                      ),
                                  ),
                                1 =>
                                  array(
                                    'type' => 'text',
                                    'text' => '<h1 style="text-align: left;"><span style="color: #ffffff;">Autumn/Winter</span></h1>',
                                  ),
                              ),
                          ),
                      ),
                  ),
                2 =>
                  array(
                    'type' => 'container',
                    'orientation' => 'horizontal',
                    'image' =>
                      array(
                        'src' => NULL,
                        'display' => 'scale',
                      ),
                    'styles' =>
                      array(
                        'block' =>
                          array(
                            'backgroundColor' => '#ffffff',
                          ),
                      ),
                    'blocks' =>
                      array(
                        0 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'spacer',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'backgroundColor' => 'transparent',
                                            'height' => '40px',
                                          ),
                                      ),
                                  ),
                                1 =>
                                  array(
                                    'type' => 'text',
                                    'text' => '<h2 style="text-align: center;"><strong>The Autumn/Winter&nbsp;Range at Tulip Park</strong></h2>
<p style="text-align: center;"><span>Lorem ipsum dolor sit amet, consectetur adipiscing elit. In elementum nunc vel est congue, a venenatis nunc aliquet. Curabitur luctus, nulla et dignissim elementum, ipsum eros fermentum nulla, non cursus eros mi eu velit. Nunc ex nibh, porta vulputate pharetra ac, placerat sed orci. Etiam enim enim, aliquet nec ligula in, ultrices iaculis dolor. Suspendisse potenti. Praesent fringilla augue ut lorem mattis, vitae fringilla nunc faucibus.</span></p>',
                                  ),
                                2 =>
                                  array(
                                    'type' => 'spacer',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
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
                  array(
                    'type' => 'container',
                    'orientation' => 'horizontal',
                    'image' =>
                      array(
                        'src' => NULL,
                        'display' => 'scale',
                      ),
                    'styles' =>
                      array(
                        'block' =>
                          array(
                            'backgroundColor' => 'transparent',
                          ),
                      ),
                    'blocks' =>
                      array(
                        0 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'image',
                                    'link' => '',
                                    'src' => $this->template_image_url . '/Fashion-Items-1.jpg',
                                    'alt' => 'Fashion-Items-1',
                                    'fullWidth' => true,
                                    'width' => '364px',
                                    'height' => '291px',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'textAlign' => 'center',
                                          ),
                                      ),
                                  ),
                              ),
                          ),
                        1 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'spacer',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'backgroundColor' => 'transparent',
                                            'height' => '36px',
                                          ),
                                      ),
                                  ),
                                1 =>
                                  array(
                                    'type' => 'text',
                                    'text' => '<h3><strong>Title Goes Here</strong></h3>
<p><span>Lorem ipsum dolor sit amet, consectetur adipiscing elit. In elementum nunc vel est congue, a venenatis nunc aliquet. Curabitur luctus, nulla et dignissim elementum, ipsum eros fermentum nulla, non cursus eros mi eu velit. Nunc ex nibh, porta vulputate pharetra ac, placerat sed orci.</span></p>',
                                  ),
                              ),
                          ),
                      ),
                  ),
                4 =>
                  array(
                    'type' => 'container',
                    'orientation' => 'horizontal',
                    'image' =>
                      array(
                        'src' => NULL,
                        'display' => 'scale',
                      ),
                    'styles' =>
                      array(
                        'block' =>
                          array(
                            'backgroundColor' => 'transparent',
                          ),
                      ),
                    'blocks' =>
                      array(
                        0 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'spacer',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'backgroundColor' => 'transparent',
                                            'height' => '36px',
                                          ),
                                      ),
                                  ),
                                1 =>
                                  array(
                                    'type' => 'text',
                                    'text' => '<h3><strong>Title Goes Here</strong></h3>
<p><span>Lorem ipsum dolor sit amet, consectetur adipiscing elit. In elementum nunc vel est congue, a venenatis nunc aliquet. Curabitur luctus, nulla et dignissim elementum, ipsum eros fermentum nulla, non cursus eros mi eu velit. Nunc ex nibh, porta vulputate pharetra ac, placerat sed orci.</span></p>',
                                  ),
                              ),
                          ),
                        1 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'image',
                                    'link' => '',
                                    'src' => $this->template_image_url . '/Fashion-Items-2.jpg',
                                    'alt' => 'Fashion-Items-2',
                                    'fullWidth' => true,
                                    'width' => '364px',
                                    'height' => '291px',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'textAlign' => 'center',
                                          ),
                                      ),
                                  ),
                              ),
                          ),
                      ),
                  ),
                5 =>
                  array(
                    'type' => 'container',
                    'orientation' => 'horizontal',
                    'image' =>
                      array(
                        'src' => NULL,
                        'display' => 'scale',
                      ),
                    'styles' =>
                      array(
                        'block' =>
                          array(
                            'backgroundColor' => 'transparent',
                          ),
                      ),
                    'blocks' =>
                      array(
                        0 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'image',
                                    'link' => '',
                                    'src' => $this->template_image_url . '/Fashion-Items-3.jpg',
                                    'alt' => 'Fashion-Items-3',
                                    'fullWidth' => true,
                                    'width' => '364px',
                                    'height' => '291px',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'textAlign' => 'center',
                                          ),
                                      ),
                                  ),
                              ),
                          ),
                        1 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'spacer',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'backgroundColor' => 'transparent',
                                            'height' => '36px',
                                          ),
                                      ),
                                  ),
                                1 =>
                                  array(
                                    'type' => 'text',
                                    'text' => '<h3><strong>Title Goes Here</strong></h3>
<p><span>Lorem ipsum dolor sit amet, consectetur adipiscing elit. In elementum nunc vel est congue, a venenatis nunc aliquet. Curabitur luctus, nulla et dignissim elementum, ipsum eros fermentum nulla, non cursus eros mi eu velit. Nunc ex nibh, porta vulputate pharetra ac, placerat sed orci.</span></p>',
                                  ),
                              ),
                          ),
                      ),
                  ),
                6 =>
                  array(
                    'type' => 'container',
                    'orientation' => 'horizontal',
                    'image' =>
                      array(
                        'src' => NULL,
                        'display' => 'scale',
                      ),
                    'styles' =>
                      array(
                        'block' =>
                          array(
                            'backgroundColor' => 'transparent',
                          ),
                      ),
                    'blocks' =>
                      array(
                        0 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'spacer',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'backgroundColor' => 'transparent',
                                            'height' => '35px',
                                          ),
                                      ),
                                  ),
                                1 =>
                                  array(
                                    'type' => 'button',
                                    'text' => 'Check out the full range here',
                                    'url' => '',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'backgroundColor' => '#cdcdcd',
                                            'borderColor' => '#e4e4e4',
                                            'borderWidth' => '1px',
                                            'borderRadius' => '3px',
                                            'borderStyle' => 'solid',
                                            'width' => '288px',
                                            'lineHeight' => '40px',
                                            'fontColor' => '#000000',
                                            'fontFamily' => 'Arial',
                                            'fontSize' => '16px',
                                            'fontWeight' => 'bold',
                                            'textAlign' => 'center',
                                          ),
                                      ),
                                  ),
                                2 =>
                                  array(
                                    'type' => 'divider',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'backgroundColor' => 'transparent',
                                            'padding' => '13px',
                                            'borderStyle' => 'solid',
                                            'borderWidth' => '1px',
                                            'borderColor' => '#aaaaaa',
                                          ),
                                      ),
                                  ),
                                3 =>
                                  array(
                                    'type' => 'spacer',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'backgroundColor' => 'transparent',
                                            'height' => '20px',
                                          ),
                                      ),
                                  ),
                              ),
                          ),
                      ),
                  ),
                7 =>
                  array(
                    'type' => 'container',
                    'orientation' => 'horizontal',
                    'image' =>
                      array(
                        'src' => NULL,
                        'display' => 'scale',
                      ),
                    'styles' =>
                      array(
                        'block' =>
                          array(
                            'backgroundColor' => 'transparent',
                          ),
                      ),
                    'blocks' =>
                      array(
                        0 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'text',
                                    'text' => '<h2 style="text-align: center;"><strong>New in this week...</strong></h2>',
                                  ),
                              ),
                          ),
                      ),
                  ),
                8 =>
                  array(
                    'type' => 'container',
                    'orientation' => 'horizontal',
                    'image' =>
                      array(
                        'src' => NULL,
                        'display' => 'scale',
                      ),
                    'styles' =>
                      array(
                        'block' =>
                          array(
                            'backgroundColor' => 'transparent',
                          ),
                      ),
                    'blocks' =>
                      array(
                        0 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'image',
                                    'link' => '',
                                    'src' => $this->template_image_url . '/Fashion-Items-4.jpg',
                                    'alt' => 'Fashion-Items-4',
                                    'fullWidth' => true,
                                    'width' => '364px',
                                    'height' => '291px',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'textAlign' => 'center',
                                          ),
                                      ),
                                  ),
                              ),
                          ),
                        1 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'image',
                                    'link' => '',
                                    'src' => $this->template_image_url . '/Fashion-Items-5.jpg',
                                    'alt' => 'Fashion-Items-5',
                                    'fullWidth' => true,
                                    'width' => '364px',
                                    'height' => '291px',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'textAlign' => 'center',
                                          ),
                                      ),
                                  ),
                              ),
                          ),
                        2 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'image',
                                    'link' => '',
                                    'src' => $this->template_image_url . '/Fashion-Items-6.jpg',
                                    'alt' => 'Fashion-Items-6',
                                    'fullWidth' => true,
                                    'width' => '364px',
                                    'height' => '291px',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'textAlign' => 'center',
                                          ),
                                      ),
                                  ),
                              ),
                          ),
                      ),
                  ),
                9 =>
                  array(
                    'type' => 'container',
                    'orientation' => 'horizontal',
                    'image' =>
                      array(
                        'src' => NULL,
                        'display' => 'scale',
                      ),
                    'styles' =>
                      array(
                        'block' =>
                          array(
                            'backgroundColor' => '#12223b',
                          ),
                      ),
                    'blocks' =>
                      array(
                        0 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'spacer',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'backgroundColor' => '#f0f0f0',
                                            'height' => '20px',
                                          ),
                                      ),
                                  ),
                              ),
                          ),
                      ),
                  ),
                10 =>
                  array(
                    'type' => 'container',
                    'orientation' => 'horizontal',
                    'image' =>
                      array(
                        'src' => NULL,
                        'display' => 'scale',
                      ),
                    'styles' =>
                      array(
                        'block' =>
                          array(
                            'backgroundColor' => '#f0f0f0',
                          ),
                      ),
                    'blocks' =>
                      array(
                        0 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'spacer',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'backgroundColor' => 'transparent',
                                            'height' => '20px',
                                          ),
                                      ),
                                  ),
                                1 =>
                                  array(
                                    'type' => 'text',
                                    'text' => '<h2 style="text-align: center;"><strong>TULIP PARK</strong></h2>',
                                  ),
                              ),
                          ),
                        1 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'spacer',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'backgroundColor' => 'transparent',
                                            'height' => '20px',
                                          ),
                                      ),
                                  ),
                                1 =>
                                  array(
                                    'type' => 'social',
                                    'iconSet' => 'full-symbol-black',
                                    'icons' =>
                                      array(
                                        0 =>
                                          array(
                                            'type' => 'socialIcon',
                                            'iconType' => 'facebook',
                                            'link' => 'http://www.facebook.com',
                                            'image' => $this->social_icon_url . '/07-full-symbol-black/Facebook.png',
                                            'height' => '32px',
                                            'width' => '32px',
                                            'text' => 'Facebook',
                                          ),
                                        1 =>
                                          array(
                                            'type' => 'socialIcon',
                                            'iconType' => 'twitter',
                                            'link' => 'http://www.twitter.com',
                                            'image' => $this->social_icon_url . '/07-full-symbol-black/Twitter.png',
                                            'height' => '32px',
                                            'width' => '32px',
                                            'text' => 'Twitter',
                                          ),
                                        2 =>
                                          array(
                                            'type' => 'socialIcon',
                                            'iconType' => 'instagram',
                                            'link' => 'http://instagram.com',
                                            'image' => $this->social_icon_url . '/07-full-symbol-black/Instagram.png',
                                            'height' => '32px',
                                            'width' => '32px',
                                            'text' => 'Instagram',
                                          ),
                                      ),
                                  ),
                              ),
                          ),
                        2 =>
                          array(
                            'type' => 'container',
                            'orientation' => 'vertical',
                            'image' =>
                              array(
                                'src' => NULL,
                                'display' => 'scale',
                              ),
                            'styles' =>
                              array(
                                'block' =>
                                  array(
                                    'backgroundColor' => 'transparent',
                                  ),
                              ),
                            'blocks' =>
                              array(
                                0 =>
                                  array(
                                    'type' => 'spacer',
                                    'styles' =>
                                      array(
                                        'block' =>
                                          array(
                                            'backgroundColor' => 'transparent',
                                            'height' => '20px',
                                          ),
                                      ),
                                  ),
                                1 =>
                                  array(
                                    'type' => 'text',
                                    'text' => '<p style="font-size: 11px;"><span style="color: #000000;"><a href="[link:subscription_unsubscribe_url]" style="color: #000000;">Unsubscribe</a>&nbsp;|&nbsp;<a href="[link:subscription_manage_url]" style="color: #000000;">Manage subscription</a></span><br /><span style="color: #000000;">Add your postal address here!</span></p>',
                                  ),
                              ),
                          ),
                      ),
                  ),
              ),
          ),
        'globalStyles' =>
          array(
            'text' =>
              array(
                'fontColor' => '#000000',
                'fontFamily' => 'Arial',
                'fontSize' => '14px',
              ),
            'h1' =>
              array(
                'fontColor' => '#111111',
                'fontFamily' => 'Courier New',
                'fontSize' => '30px',
              ),
            'h2' =>
              array(
                'fontColor' => '#222222',
                'fontFamily' => 'Arial',
                'fontSize' => '24px',
              ),
            'h3' =>
              array(
                'fontColor' => '#333333',
                'fontFamily' => 'Verdana',
                'fontSize' => '18px',
              ),
            'link' =>
              array(
                'fontColor' => '#008282',
                'textDecoration' => 'underline',
              ),
            'wrapper' =>
              array(
                'backgroundColor' => '#ffffff',
              ),
            'body' =>
              array(
                'backgroundColor' => '#f0f0f0',
              ),
          ),
        'blockDefaults' =>
          array(
            'automatedLatestContent' =>
              array(
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
                  array(
                    'text' => 'Read more',
                    'url' => '[postLink]',
                    'context' => 'automatedLatestContent.readMoreButton',
                    'styles' =>
                      array(
                        'block' =>
                          array(
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
                  array(
                    'context' => 'automatedLatestContent.divider',
                    'styles' =>
                      array(
                        'block' =>
                          array(
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
              array(
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
                  array(
                    'text' => 'Read more',
                    'url' => '[postLink]',
                    'context' => 'automatedLatestContentLayout.readMoreButton',
                    'styles' =>
                      array(
                        'block' =>
                          array(
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
                  array(
                    'context' => 'automatedLatestContentLayout.divider',
                    'styles' =>
                      array(
                        'block' =>
                          array(
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
              array(
                'text' => 'Button',
                'url' => '',
                'styles' =>
                  array(
                    'block' =>
                      array(
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
              array(
                'styles' =>
                  array(
                    'block' =>
                      array(
                        'backgroundColor' => 'transparent',
                        'padding' => '13px',
                        'borderStyle' => 'solid',
                        'borderWidth' => '3px',
                        'borderColor' => '#aaaaaa',
                      ),
                  ),
              ),
            'footer' =>
              array(
                'text' => '<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage subscription</a><br />Add your postal address here!</p>',
                'styles' =>
                  array(
                    'block' =>
                      array(
                        'backgroundColor' => 'transparent',
                      ),
                    'text' =>
                      array(
                        'fontColor' => '#222222',
                        'fontFamily' => 'Arial',
                        'fontSize' => '12px',
                        'textAlign' => 'center',
                      ),
                    'link' =>
                      array(
                        'fontColor' => '#6cb7d4',
                        'textDecoration' => 'none',
                      ),
                  ),
              ),
            'posts' =>
              array(
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
                  array(
                    'text' => 'Read more',
                    'url' => '[postLink]',
                    'context' => 'posts.readMoreButton',
                    'styles' =>
                      array(
                        'block' =>
                          array(
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
                  array(
                    'context' => 'posts.divider',
                    'styles' =>
                      array(
                        'block' =>
                          array(
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
              array(
                'iconSet' => 'default',
                'icons' =>
                  array(
                    0 =>
                      array(
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://www.facebook.com',
                        'image' => $this->social_icon_url . '/01-social/Facebook.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                    1 =>
                      array(
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com',
                        'image' => $this->social_icon_url . '/01-social/Twitter.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                  ),
              ),
            'spacer' =>
              array(
                'styles' =>
                  array(
                    'block' =>
                      array(
                        'backgroundColor' => 'transparent',
                        'height' => '486px',
                      ),
                  ),
                'type' => 'spacer',
              ),
            'header' =>
              array(
                'text' => 'Display problems?&nbsp;<a href="[link:newsletter_view_in_browser_url]">Open this email in your web browser.</a>',
                'styles' =>
                  array(
                    'block' =>
                      array(
                        'backgroundColor' => 'transparent',
                      ),
                    'text' =>
                      array(
                        'fontColor' => '#222222',
                        'fontFamily' => 'Arial',
                        'fontSize' => '12px',
                        'textAlign' => 'center',
                      ),
                    'link' =>
                      array(
                        'fontColor' => '#6cb7d4',
                        'textDecoration' => 'underline',
                      ),
                  ),
              ),
          ),
    );
  }

}
