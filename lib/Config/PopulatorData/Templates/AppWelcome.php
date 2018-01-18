<?php

namespace MailPoet\Config\PopulatorData\Templates;

class AppWelcome {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
     $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/app_welcome';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("App Welcome", 'mailpoet'),
      'description' => __("A welcome email template for your app.", 'mailpoet'),
      'categories' => json_encode(array('welcome', 'sample')),
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
          array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => '#eeeeee',
              ),
            ),
            'blocks' => array(
              array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#eeeeee',
                        'height' => '30px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => '#32b6c6',
              ),
            ),
            'blocks' => array(
              array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '40px',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/App-Signup-Logo-1.png',
                    'alt' => 'App-Signup-Logo',
                    'fullWidth' => false,
                    'width' => '80px',
                    'height' => '80px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'text',
                    'text' => '<h1 style="text-align: center; margin: 0;"><strong>Welcome to Appy</strong></h1><p style="text-align: center; margin: 0;"><span style="color: #ffffff;">Let\'s get started!</span></p>',
                  ),

                  array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/App-Signup-Header.png',
                    'alt' => 'App-Signup-Header',
                    'fullWidth' => false,
                    'width' => '1280px',
                    'height' => '500px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),

          array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => '#ffffff',
              ),
            ),
            'blocks' => array(
              array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '40px',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'text',
                    'text' => '<p style="text-align: center;">Hi [subscriber:firstname | default:subscriber],</p>
                                  <p style="text-align: center;"></p>
                                  <p style="text-align: center;">In MailPoet, you can write emails in plain text, just like in a regular email. This can make your email newsletters more personal and attention-grabbing.</p>
                                  <p style="text-align: center;"></p>
                                  <p style="text-align: center;">Is this too simple? You can still style your text with basic formatting, like <strong>bold</strong> or <em>italics.</em></p>
                                  <p style="text-align: center;"></p>
                                  <p style="text-align: center;">Finally, you can also add a call-to-action button between 2 blocks of text, like this:</p>',
                  ),
                ),
              ),
            ),
          ),
          array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => '#ffffff',
              ),
            ),
            'blocks' => array(
              array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '23px',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'button',
                    'text' => 'Get Started Here',
                    'url' => '',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#32b6c6',
                        'borderColor' => '#32b6c6',
                        'borderWidth' => '0px',
                        'borderRadius' => '40px',
                        'borderStyle' => 'solid',
                        'width' => '188px',
                        'lineHeight' => '50px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Arial',
                        'fontSize' => '18px',
                        'fontWeight' => 'normal',
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '35px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => 'transparent',
              ),
            ),
            'blocks' => array(
              array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/App-Signup-Team.jpg',
                    'alt' => 'App-Signup-Team',
                    'fullWidth' => true,
                    'width' => '1280px',
                    'height' => '700px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => '#eeeeee',
              ),
            ),
            'blocks' => array(
              array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '30px',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/App-Signup-Logo-Footer.png',
                    'alt' => 'App-Signup-Logo-Footer',
                    'fullWidth' => false,
                    'width' => '50px',
                    'height' => '50px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'text',
                    'text' => '<p style="text-align: center; font-size: 12px;"><strong>Appy</strong></p>
                                <p style="text-align: center; font-size: 12px;"><span>Address Line 1</span></p>
                                <p style="text-align: center; font-size: 12px;"><span>Address Line 2</span></p>
                                <p style="text-align: center; font-size: 12px;"><span>City</span></p>
                                <p style="text-align: center; font-size: 12px;"><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a><span> | </span><a href="[link:subscription_manage_url]">Manage subscription</a></p>',
                  ),
                  array(
                    'type' => 'social',
                    'iconSet' => 'full-symbol-color',
                    'icons' => array(
                      array(
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://www.facebook.com',
                        'image' => $this->social_icon_url . '/06-full-symbol-color/Facebook.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                      array(
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com',
                        'image' => $this->social_icon_url . '/06-full-symbol-color/Twitter.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                      array(
                        'type' => 'socialIcon',
                        'iconType' => 'youtube',
                        'link' => 'http://www.youtube.com',
                        'image' => $this->social_icon_url . '/06-full-symbol-color/Youtube.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Youtube',
                      ),
                      array(
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
                  array(
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
          'fontColor' => '#404040',
          'fontFamily' => 'Arial',
          'fontSize' => '15px',
        ),
        'h1' => array(
          'fontColor' => '#ffffff',
          'fontFamily' => 'Arial',
          'fontSize' => '26px',
        ),
        'h2' => array(
          'fontColor' => '#404040',
          'fontFamily' => 'Arial',
          'fontSize' => '22px',
        ),
        'h3' => array(
          'fontColor' => '#32b6c6',
          'fontFamily' => 'Arial',
          'fontSize' => '18px',
        ),
        'link' => array(
          'fontColor' => '#32b6c6',
          'textDecoration' => 'underline',
        ),
        'wrapper' => array(
          'backgroundColor' => '#ffffff',
        ),
        'body' => array(
          'backgroundColor' => '#eeeeee',
        ),
      ),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/app-welcome-email.jpg';
  }

}