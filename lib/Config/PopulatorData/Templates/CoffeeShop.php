<?php

namespace MailPoet\Config\PopulatorData\Templates;

class CoffeeShop {

  private $social_icon_url;
  private $template_image_url;

  function __construct($assets_url) {
     $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/franks-roast-house';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Coffee Shop", 'mailpoet'),
      'description' => __("Coffee and sugar in your coffee?", 'mailpoet'),
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
                        'backgroundColor' => '#ccc6c6',
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '40px',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'image',
                    'link' => 'http://www.example.com',
                    'src' => $this->template_image_url . '/header-v2.jpg',
                    'alt' => 'Frank\'s Café',
                    'fullWidth' => true,
                    'width' => '600px',
                    'height' => '220px',
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
                    'type' => 'text',
                    'text' => '<p>Hi there [subscriber:firstname | default:coffee drinker]</p>
                      <p></p>
                      <p>Sit back and enjoy your favorite roast as you read this week\'s newsletter. </p>',
                  ),
                  5 => array(
                    'type' => 'image',
                    'link' => 'http://example.org',
                    'src' => $this->template_image_url . '/coffee-grain.jpg',
                    'alt' => 'Coffee grain',
                    'fullWidth' => true,
                    'width' => '1599px',
                    'height' => '777px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  6 => array(
                    'type' => 'text',
                    'text' => '<h1 style="text-align: center;">--- Guest Roaster: <em>Brew Bros. ---</em></h1>
                      <p><em></em></p>
                      <p>Visit our Center Avenue store to try the latest guest coffee from Brew Bros, a local coffee roaster. This young duo started only two years ago, but have quickly gained popularity through pop-up shops, local events, and collaborations with food trucks.</p>
                      <p></p>
                      <blockquote>
                      <p><span style="color: #ff6600;"><em><strong>Tasting notes:</strong> A rich, caramel flavor with subtle hints of molasses. The perfect wake-up morning espresso!</em></span></p>
                      </blockquote>',
                  ),
                  7 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '22px',
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
                'backgroundColor' => '#ebebeb',
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
                  1 => array(
                    'type' => 'text',
                    'text' => '<h2>Sandwich Competition</h2>',
                  ),
                  2 => array(
                    'type' => 'image',
                    'link' => 'http://example.org',
                    'src' => $this->template_image_url . '/sandwich.jpg',
                    'alt' => 'Sandwich',
                    'fullWidth' => false,
                    'width' => '640px',
                    'height' => '344px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  3 => array(
                    'type' => 'text',
                    'text' => '<p>Have an idea for the Next Great Sandwich? Tell us! We\'re offering free lunch for a month if you can invent an awesome new sandwich for our menu.</p>
                      <p></p>
                      <p>Simply tweet your ideas to <a href="http://www.example.com" title="This isn\'t a real twitter account">@franksroasthouse</a> and use #sandwichcomp and we\'ll let you know if you\'re a winner.</p>',
                  ),
                  4 => array(
                    'type' => 'button',
                    'text' => 'Find out more',
                    'url' => 'http://example.org',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#604b4b',
                        'borderColor' => '#443232',
                        'borderWidth' => '1px',
                        'borderRadius' => '3px',
                        'borderStyle' => 'solid',
                        'width' => '180px',
                        'lineHeight' => '34px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Arial',
                        'fontSize' => '14px',
                        'fontWeight' => 'normal',
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
                    'text' => '<h3 style="text-align: center;">Follow Us</h3>',
                  ),
                  7 => array(
                    'type' => 'social',
                    'iconSet' => 'full-symbol-black',
                    'icons' => array(
                      0 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://www.facebook.com/mailpoetplugin',
                        'image' => $this->social_icon_url . '/07-full-symbol-black/Facebook.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                      1 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com/mailpoet',
                        'image' => $this->social_icon_url . '/07-full-symbol-black/Twitter.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                      2 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'instagram',
                        'link' => 'http://www.instagram.com/test',
                        'image' => $this->social_icon_url . '/07-full-symbol-black/Instagram.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Instagram',
                      ),
                      3 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'website',
                        'link' => 'http://www.mailpoet.com',
                        'image' => $this->social_icon_url . '/07-full-symbol-black/Website.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Website',
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
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '40px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'text',
                    'text' => '<h2>New Store Opening!</h2>',
                  ),
                  2 => array(
                    'type' => 'image',
                    'link' => 'http://example.org',
                    'src' => $this->template_image_url . '/map-v2.jpg',
                    'alt' => 'Map',
                    'fullWidth' => false,
                    'width' => '636px',
                    'height' => '342px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  3 => array(
                    'type' => 'text',
                    'text' => '<p>Watch out Broad Street, we\'re coming to you very soon! </p>
                      <p></p>
                      <p>Keep an eye on your inbox, as we\'ll have some special offers for our email subscribers plus an exclusive launch party invite!<br /><br /></p>',
                  ),
                  4 => array(
                    'type' => 'text',
                    'text' => '<h2>New and Improved Hours!</h2>
                      <p></p>
                      <p>Frank\'s is now open even later, so you can get your caffeine fix all day (and night) long! Here\'s our new opening hours:</p>
                      <p></p>
                      <ul>
                      <li>Monday - Thursday: 6am - 12am</li>
                      <li>Friday - Saturday: 6am - 1:30am</li>
                      <li>Sunday: 7:30am - 11pm</li>
                      </ul>',
                  ),
                  5 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '33px',
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
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'footer',
                    'text' => '<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage subscription</a><br />12345 MailPoet Drive, EmailVille, 76543</p>',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#a9a7a7',
                      ),
                      'text' => array(
                        'fontColor' => '#000000',
                        'fontFamily' => 'Arial',
                        'fontSize' => '12px',
                        'textAlign' => 'right',
                      ),
                      'link' => array(
                        'fontColor' => '#000000',
                        'textDecoration' => 'underline',
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
          'fontColor' => '#000000',
          'fontFamily' => 'Arial',
          'fontSize' => '14px',
        ),
        'h1' => array(
          'fontColor' => '#604b4b',
          'fontFamily' => 'Lucida',
          'fontSize' => '30px',
        ),
        'h2' => array(
          'fontColor' => '#5c4242',
          'fontFamily' => 'Lucida',
          'fontSize' => '22px',
        ),
        'h3' => array(
          'fontColor' => '#333333',
          'fontFamily' => 'Lucida',
          'fontSize' => '20px',
        ),
        'link' => array(
          'fontColor' => '#047da7',
          'textDecoration' => 'underline',
        ),
        'wrapper' => array(
          'backgroundColor' => '#ffffff',
        ),
        'body' => array(
          'backgroundColor' => '#ccc6c6',
        ),
      ),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/coffee-shop.jpg';
  }

}