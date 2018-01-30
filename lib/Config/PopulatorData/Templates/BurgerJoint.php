<?php

namespace MailPoet\Config\PopulatorData\Templates;

class BurgerJoint {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/burger_joint';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Burger Joint", 'mailpoet'),
      'description' => __("Add more or less ketchup or mayo to this restaurant template.", 'mailpoet'),
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
                    'link' => 'http://example.org',
                    'src' => $this->template_image_url . '/header.jpg',
                    'alt' => 'Joe\'s Burger Joint',
                    'fullWidth' => true,
                    'width' => '660px',
                    'height' => '100px',
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
                    'link' => 'http://example.org',
                    'src' => $this->template_image_url . '/burger-03.jpg',
                    'alt' => 'burger-03',
                    'fullWidth' => true,
                    'width' => '1200px',
                    'height' => '613px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
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
                    'type' => 'button',
                    'text' => 'Make a reservation',
                    'url' => 'http://example.org',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#d83b3b',
                        'borderColor' => '#ffffff',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '225px',
                        'lineHeight' => '50px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Verdana',
                        'fontSize' => '18px',
                        'fontWeight' => 'normal',
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'text',
                    'text' => '<h1 style="text-align: center;"><em>Upgrade! Add these sides</em></h1>',
                  ),
                  array(
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
                    'link' => 'http://example.org',
                    'src' => $this->template_image_url . '/fries-01.jpg',
                    'alt' => 'fries-01',
                    'fullWidth' => false,
                    'width' => '1000px',
                    'height' => '1500px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'text',
                    'text' => '<blockquote><p>Cras cursus viverra nulla non tempus. Curabitur sed neque vel sapien! - Morbi ullamcorper, Tellus Diam</p></blockquote>',
                  ),
                ),
              ),
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
                    'link' => 'http://example.org',
                    'src' => $this->template_image_url . '/rolls-01.jpg',
                    'alt' => 'rolls-01',
                    'fullWidth' => false,
                    'width' => '1000px',
                    'height' => '1500px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'text',
                    'text' => '<blockquote><p>Morbi ex diam, venenatis a efficitur et, iaculis at nibh. - Quis Ullamcorper, Tortor Ligula</p></blockquote>',
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
                    'type' => 'divider',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'padding' => '34px',
                        'borderStyle' => 'dashed',
                        'borderWidth' => '3px',
                        'borderColor' => '#aaaaaa',
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
                'backgroundColor' => '#e0e0e0',
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
                    'type' => 'text',
                    'text' => '<h2 style="text-align: center;">Find us at these locations</h2>',
                  ),
                  array(
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
          array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => '#e0e0e0',
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
                    'type' => 'text',
                    'text' => '<h3><span style="text-decoration: underline;"><em>Denver</em></span></h3><p>1263 Schoville Street</p><p>53355 DENVER</p><p>CO</p>',
                  ),
                ),
              ),
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
                    'type' => 'text',
                    'text' => '<h3><span style="text-decoration: underline;"><em>Fort Collins</em></span></h3><p><em></em>157 Maine Street</p><p>86432 FORT COLLINS<br />CO</p><p></p>',
                  ),
                ),
              ),
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
                    'type' => 'text',
                    'text' => '<h3><span style="text-decoration: underline;"><em>Pueblo</em></span></h3><p><em></em>5390 York Avenue</p><p>64297 Pueblo</p><p>CO</p>',
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
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '30px',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'social',
                    'iconSet' => 'full-symbol-black',
                    'icons' => array(
                      array(
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://example.com',
                        'image' => $this->social_icon_url . '/07-full-symbol-black/Facebook.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                      array(
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://example.com',
                        'image' => $this->social_icon_url . '/07-full-symbol-black/Twitter.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
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
                  array(
                    'type' => 'header',
                    'text' => '<p><a href="[link:newsletter_view_in_browser_url]">View&nbsp;this email in your web browser</a></p>',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                      ),
                      'text' => array(
                        'fontColor' => '#222222',
                        'fontFamily' => 'Verdana',
                        'fontSize' => '12px',
                        'textAlign' => 'center',
                      ),
                      'link' => array(
                        'fontColor' => '#d83b3b',
                        'textDecoration' => 'underline',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'footer',
                    'text' => '<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage subscription</a><br />Address: Colorado</p>',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                      ),
                      'text' => array(
                        'fontColor' => '#222222',
                        'fontFamily' => 'Verdana',
                        'fontSize' => '12px',
                        'textAlign' => 'center',
                      ),
                      'link' => array(
                        'fontColor' => '#d83b3b',
                        'textDecoration' => 'none',
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
          'fontColor' => '#434343',
          'fontFamily' => 'Tahoma',
          'fontSize' => '16px',
        ),
        'h1' => array(
          'fontColor' => '#222222',
          'fontFamily' => 'Verdana',
          'fontSize' => '24px',
        ),
        'h2' => array(
          'fontColor' => '#222222',
          'fontFamily' => 'Verdana',
          'fontSize' => '22px',
        ),
        'h3' => array(
          'fontColor' => '#222222',
          'fontFamily' => 'Verdana',
          'fontSize' => '20px',
        ),
        'link' => array(
          'fontColor' => '#21759B',
          'textDecoration' => 'underline',
        ),
        'wrapper' => array(
          'backgroundColor' => '#f0f0f0',
        ),
        'body' => array(
          'backgroundColor' => '#ffffff',
        ),
      ),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/burger-joint.jpg';
  }

}
