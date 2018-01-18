<?php

namespace MailPoet\Config\PopulatorData\Templates;

class KickOff {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
     $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/kick_off';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Kick-Off", 'mailpoet'),
      'description' => __("Sporty green template for your team or sport event.", 'mailpoet'),
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/football-header.jpg',
                    'alt' => 'football-header',
                    'fullWidth' => true,
                    'width' => '1320px',
                    'height' => '540px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'text',
                    'text' => '<h1 style="text-align: center;"><strong>ALL THE LATEST MATCH RESULTS &amp; NEWS FROM THE SUNDAYS CLUB</strong></h1>',
                  ),
                  3 => array(
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/football-player-1.jpeg',
                    'alt' => 'football-player',
                    'fullWidth' => false,
                    'width' => '600px',
                    'height' => '840px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'text',
                    'text' => '<p><em>Nam convallis lorem tellus, eget sodales magna semper quis.</em></p>',
                  ),
                  2 => array(
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
                    'text' => '<h2>North</h2>
                        <p>Nottington 0-1 East Lettersley</p>
                        <p>Little Bickburgh 2-1 Rockingham</p>
                        <p></p>
                        <h2>South</h2>
                        <p>Richmond West 1-0 Offington</p>
                        <p>Shorleton 5-2 Garphingham</p>
                        <p>Westwood 1-3 Chesham</p>
                        <p></p>
                        <h2>West</h2>
                        <p>Millham 4-2 Dunn Village</p>
                        <p>Emmington 1-1 Finham</p>
                        <p>Little Forest 0-2 Winton</p>
                        <p></p>
                        <h2>East</h2>
                        <p>Southfield 2-1 Fincham</p>
                        <p>High Ridlington 0-1 Benham</p>
                        <p>Tinton 4-6 Dortington</p>',
                  ),
                  1 => array(
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
                    'type' => 'divider',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'padding' => '25px',
                        'borderStyle' => 'ridge',
                        'borderWidth' => '5px',
                        'borderColor' => '#484747',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'text',
                    'text' => '<h1 style="text-align: center;">MATCH REPORTS</h1>',
                  ),
                  2 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '21px',
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
                    'type' => 'text',
                    'text' => '<h2>Branham United vs Finkley</h2>
                        <p>Vestibulum consectetur, quam sed tristique feugiat, elit sapien molestie mi, eu dapibus eros sapien ut risus. Nullam non scelerisque ligula.</p>
                        <p></p>
                        <p>Donec vitae nunc tempus, elementum magna et, ultrices velit. Sed eu consequat sapien, at dictum diam. Sed tristique egestas justo sit amet vulputate. Proin rhoncus sem eu odio ultricies ultrices.</p>',
                  ),
                  1 => array(
                    'type' => 'button',
                    'text' => 'READ MORE',
                    'url' => 'http://www.google.co.uk',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#61cc5a',
                        'borderColor' => '#2f6a2c',
                        'borderWidth' => '3px',
                        'borderRadius' => '5px',
                        'borderStyle' => 'solid',
                        'width' => '180px',
                        'lineHeight' => '40px',
                        'fontColor' => '#252525',
                        'fontFamily' => 'Lucida',
                        'fontSize' => '14px',
                        'fontWeight' => 'bold',
                        'textAlign' => 'left',
                      ),
                    ),
                  ),
                  2 => array(
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
                    'text' => '<h2 style="text-align: center;">Champton Albion vs Swanhall</h2>
                        <p>Aenean a arcu egestas, tincidunt nisi ut, mollis arcu. Sed eget dapibus nisi. Quisque tortor mi, consequat ut erat et, porta imperdiet dui.</p>
                        <p></p>
                        <p>Mauris vestibulum tortor ut justo luctus blandit. Vestibulum mollis sollicitudin tempor. Duis gravida, dui quis eleifend scelerisque, libero orci semper metus, sed maximus odio tortor ac sem.</p>',
                  ),
                  1 => array(
                    'type' => 'button',
                    'text' => 'READ MORE',
                    'url' => 'https://www.google.co.uk',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#61cc5a',
                        'borderColor' => '#2f6a2c',
                        'borderWidth' => '3px',
                        'borderRadius' => '5px',
                        'borderStyle' => 'solid',
                        'width' => '180px',
                        'lineHeight' => '40px',
                        'fontColor' => '#252525',
                        'fontFamily' => 'Lucida',
                        'fontSize' => '14px',
                        'fontWeight' => 'bold',
                        'textAlign' => 'left',
                      ),
                    ),
                  ),
                  2 => array(
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
                'backgroundColor' => '#8aeb83',
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
                        'height' => '28px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'text',
                    'text' => '<h1 style="text-align: center;"><strong><span style="color: #333333;">FROM OUR ONLINE STORE</span></strong></h1>',
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
                'backgroundColor' => '#8aeb83',
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/shoes.jpg',
                    'alt' => 'shoes',
                    'fullWidth' => false,
                    'width' => '400px',
                    'height' => '400px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'text',
                    'text' => '<h3 style="text-align: center;"><strong>Team Colours Laces</strong></h3><p style="text-align: center;"><span style="color: #333333;">Donec imperdiet<em><br /></em>Tortor tincidunt, luctus libero vel, dapibus quam</span></p>',
                  ),
                  3 => array(
                    'type' => 'button',
                    'text' => 'SHOP',
                    'url' => '',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#61cc5a',
                        'borderColor' => '#2f6a2c',
                        'borderWidth' => '3px',
                        'borderRadius' => '5px',
                        'borderStyle' => 'solid',
                        'width' => '180px',
                        'lineHeight' => '40px',
                        'fontColor' => '#252525',
                        'fontFamily' => 'Lucida',
                        'fontSize' => '14px',
                        'fontWeight' => 'bold',
                        'textAlign' => 'left',
                      ),
                    ),
                  ),
                  4 => array(
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
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/football.jpg',
                    'alt' => 'football',
                    'fullWidth' => false,
                    'width' => '400px',
                    'height' => '400px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'text',
                    'text' => '<h3 style="text-align: center;"><strong>Match Quality Balls</strong></h3><p style="text-align: center;"><span style="color: #333333;">Donec vulputate tempor auctor purus sit amet cursus ultricies</span></p>',
                  ),
                  3 => array(
                    'type' => 'button',
                    'text' => 'SHOP',
                    'url' => '',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#61cc5a',
                        'borderColor' => '#2f6a2c',
                        'borderWidth' => '3px',
                        'borderRadius' => '5px',
                        'borderStyle' => 'solid',
                        'width' => '180px',
                        'lineHeight' => '40px',
                        'fontColor' => '#252525',
                        'fontFamily' => 'Lucida',
                        'fontSize' => '14px',
                        'fontWeight' => 'bold',
                        'textAlign' => 'left',
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
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/plant-pot.jpg',
                    'alt' => 'plant-pot',
                    'fullWidth' => false,
                    'width' => '400px',
                    'height' => '400px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'text',
                    'text' => '<h3 style="text-align: center;"><strong>Football Plant Pots</strong></h3><p style="text-align: center;"><span style="color: #333333;">Libero tortor aliquet metus eget efficitur est lorem sit amet purus</span></p>',
                  ),
                  3 => array(
                    'type' => 'button',
                    'text' => 'SHOP',
                    'url' => '',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#61cc5a',
                        'borderColor' => '#2f6a2c',
                        'borderWidth' => '3px',
                        'borderRadius' => '5px',
                        'borderStyle' => 'solid',
                        'width' => '180px',
                        'lineHeight' => '40px',
                        'fontColor' => '#252525',
                        'fontFamily' => 'Lucida',
                        'fontSize' => '14px',
                        'fontWeight' => 'bold',
                        'textAlign' => 'left',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          6 => array(
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
                        'height' => '40px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'footer',
                    'text' => '<a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage subscription</a><br /><b>Add your postal address here!</b>',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                      ),
                      'text' => array(
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Lucida',
                        'fontSize' => '12px',
                        'textAlign' => 'center',
                      ),
                      'link' => array(
                        'fontColor' => '#ffffff',
                        'textDecoration' => 'underline',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '23px',
                      ),
                    ),
                  ),
                  3 => array(
                    'type' => 'social',
                    'iconSet' => 'full-symbol-grey',
                    'icons' => array(
                      0 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://www.facebook.com',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Facebook.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                      1 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Twitter.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                      2 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'youtube',
                        'link' => 'http://www.youtube.com',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Youtube.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Youtube',
                      ),
                      3 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'email',
                        'link' => '',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Email.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Email',
                      ),
                    ),
                  ),
                  4 => array(
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
        ),
      ),
      'globalStyles' => array(
        'text' => array(
          'fontColor' => '#ffffff',
          'fontFamily' => 'Georgia',
          'fontSize' => '16px',
        ),
        'h1' => array(
          'fontColor' => '#ffffff',
          'fontFamily' => 'Lucida',
          'fontSize' => '24px',
        ),
        'h2' => array(
          'fontColor' => '#61cc5a',
          'fontFamily' => 'Lucida',
          'fontSize' => '22px',
        ),
        'h3' => array(
          'fontColor' => '#333333',
          'fontFamily' => 'Lucida',
          'fontSize' => '20px',
        ),
        'link' => array(
          'fontColor' => '#21759B',
          'textDecoration' => 'underline',
        ),
        'wrapper' => array(
          'backgroundColor' => '#252525',
        ),
        'body' => array(
          'backgroundColor' => '#eaeaea',
        ),
      ),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/sports.jpg';
  }

}