<?php

namespace MailPoet\Config\PopulatorData\Templates;

class PieceOfCake {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
     $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/piece_of_cake';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Piece of cake", 'mailpoet'),
      'description' => __("Baked with plenty of images.", 'mailpoet'),
      'categories' => json_encode(array('standard', 'sample')),
      'readonly' => 1,
      'thumbnail' => $this->getThumbnail(),
      'body' => json_encode($this->getBody()),
    );
  }

  private function getBody() {
    return array(
      'content' => array(
        'type' => 'container',
        'orientation' => 'vertical',
        'styles' => array(
          'block' => array(
            'backgroundColor' => 'transparent',
          ),
        ),
        'blocks' => array(
          0 => array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => '#ffffff',
              ),
            ),
            'blocks' => array(
              0 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'header',
                    'text' => '<p><strong>Open daily from 9am to 9pm |&nbsp;<a href="[link:newsletter_view_in_browser_url]">View Online</a></strong></p>',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#ececeb',
                      ),
                      'text' => array(
                        'fontColor' => '#606060',
                        'fontFamily' => 'Arial',
                        'fontSize' => '13px',
                        'textAlign' => 'right',
                      ),
                      'link' => array(
                        'fontColor' => '#d42b2b',
                        'textDecoration' => 'none',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '30px',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'image',
                    'link' => '',
                    'src' =>  $this->template_image_url . '/Restaurant-Bakery-Logo-1.png',
                    'alt' => 'Restaurant-Bakery-Logo-1',
                    'fullWidth' => true,
                    'width' => '1280px',
                    'height' => '180px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  3 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '30px',
                      ),
                    ),
                  ),
                  4 => array(
                    'type' => 'image',
                    'link' => '',
                    'src' =>  $this->template_image_url . '/Restaurant-Bakery-Header.jpg',
                    'alt' => 'Restaurant-Bakery-Header',
                    'fullWidth' => true,
                    'width' => '1280px',
                    'height' => '1600px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  5 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '40px',
                      ),
                    ),
                  ),
                  6 => array(
                    'type' => 'text',
                    'text' => '<h1 style="text-align: center;"><strong>It\'s our Birthday!</strong></h1>',
                  ),
                  7 => array(
                    'type' => 'text',
                    'text' => '<h3 style="text-align: center; line-height: 1.4;">To celebrate, we\'re adding a slice of our Birthday cake to every order. Pop in this weekend to use our special offer code and enjoy!</h3>',
                  ),
                  8 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          1 => array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => 'transparent',
              ),
            ),
            'blocks' => array(
              0 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                ),
              ),
              1 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'text',
                    'text' => '<p style="text-align: center; border: 3px dashed #d42b2b; color: #d42b2b; padding: 10px; font-size: 24px;"><strong>HAPPYBDAY</strong></p>',
                  ),
                ),
              ),
              2 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          2 => array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => 'transparent',
              ),
            ),
            'blocks' => array(
              0 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '50px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          3 => array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => '#ececeb',
              ),
            ),
            'blocks' => array(
              0 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          4 => array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => '#ececeb',
              ),
            ),
            'blocks' => array(
              0 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'text',
                    'text' => '<p style="background-color: #ececeb; line-height: 1.3;"><span style="font-weight: 600;"><span style="font-size: 12px; text-align: center;">Add your postal address here.</span></span></p>',
                  ),
                ),
              ),
              1 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'social',
                    'iconSet' => 'full-symbol-color',
                    'icons' => array(
                      0 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://www.facebook.com',
                        'image' => $this->social_icon_url . '/06-full-symbol-color/Facebook.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                      1 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com',
                        'image' => $this->social_icon_url . '/06-full-symbol-color/Twitter.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                      2 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'youtube',
                        'link' => 'http://www.youtube.com',
                        'image' => $this->social_icon_url . '/06-full-symbol-color/Youtube.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Youtube',
                      ),
                      3 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'instagram',
                        'link' => 'http://instagram.com',
                        'image' => $this->social_icon_url . '/06-full-symbol-color/Instagram.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Instagram',
                      ),
                    ),
                  ),
                ),
              ),
              2 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'text',
                    'text' => '<p style="text-align: right; line-height: 1.3;"><strong><a href="[link:subscription_unsubscribe_url]" style="color: #d42b2b; text-decoration: none; font-size: 12px; text-align: center;">Unsubscribe</a></strong></p>
                      <p style="text-align: right; line-height: 1.3;"><strong><a href="[link:subscription_manage_url]" style="color: #d42b2b; text-decoration: none; font-size: 12px; text-align: center;">Manage&nbsp;Subscription</a></strong></p>',
                  ),
                ),
              ),
            ),
          ),
          5 => array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => '#ececeb',
              ),
            ),
            'blocks' => array(
              0 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
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
      'globalStyles' => array(
        'text' => array(
          'fontColor' => '#606060',
          'fontFamily' => 'Arial',
          'fontSize' => '16px',
        ),
        'h1' => array(
          'fontColor' => '#606060',
          'fontFamily' => 'Arial',
          'fontSize' => '30px',
        ),
        'h2' => array(
          'fontColor' => '#d42b2b',
          'fontFamily' => 'Arial',
          'fontSize' => '24px',
        ),
        'h3' => array(
          'fontColor' => '#606060',
          'fontFamily' => 'Arial',
          'fontSize' => '20px',
        ),
        'link' => array(
          'fontColor' => '#d42b2b',
          'textDecoration' => 'underline',
        ),
        'wrapper' => array(
          'backgroundColor' => '#ffffff',
        ),
        'body' => array(
          'backgroundColor' => '#ececeb',
        ),
      ),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/piece-of-cake.jpg';
  }

}