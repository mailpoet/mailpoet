<?php

namespace MailPoet\Config\PopulatorData\Templates;

class TravelNomads {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
     $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/travel_nomads';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Travel Nomads", 'mailpoet'),
      'description' => __("Ideal for sharing your travel adventures.", 'mailpoet'),
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
                    'link' => 'http://example.org',
                    'src' => $this->template_image_url . '/header-1.png',
                    'alt' => 'Travelling Tales with Jane & Steven',
                    'fullWidth' => true,
                    'width' => '660px',
                    'height' => '165px',
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
                        'height' => '30px',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'text',
                    'text' => '<h1 style="text-align: center;">Hi&nbsp;[subscriber:firstname | default:reader]!</h1>
                      <p></p>
                      <p>Donec viverra arcu nec elit congue pellentesque. In ac dictum magna. Morbi sit amet accumsan augue. Cras cursus viverra nulla non tempus. Curabitur sed neque vel sapien feugiat mattis. Morbi ullamcorper tellus diam, sed rutrum nisi faucibus at.</p>',
                  ),
                  3 => array(
                    'type' => 'social',
                    'iconSet' => 'circles',
                    'icons' => array(
                      0 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://example.com',
                        'image' => $this->social_icon_url . '/03-circles/Twitter.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                    ),
                  ),
                  4 => array(
                    'type' => 'text',
                    'text' => '<h1 style="text-align: center;"><strong>Photos from Rotarua</strong></h1>',
                  ),
                  5 => array(
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
                    'link' => 'http://example.org',
                    'src' => $this->template_image_url . '/hot-thermals-1.jpg',
                    'alt' => 'hot thermals',
                    'fullWidth' => false,
                    'width' => '640px',
                    'height' => '425px',
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
                        'height' => '40px',
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
                    'type' => 'image',
                    'link' => 'http://example.org',
                    'src' => $this->template_image_url . '/5398660557_e5e338357e_z.jpg',
                    'alt' => 'The view from our campsite',
                    'fullWidth' => false,
                    'width' => '640px',
                    'height' => '425px',
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
                        'height' => '40px',
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
                    'type' => 'image',
                    'link' => 'http://example.org',
                    'src' => $this->template_image_url . '/5399212952_b3fea8fffb_z.jpg',
                    'alt' => 'Red sky at night',
                    'fullWidth' => false,
                    'width' => '640px',
                    'height' => '425px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
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
                    'type' => 'image',
                    'link' => 'http://example.org',
                    'src' => $this->template_image_url . '/5399283298_0d2cd66e9f_z.jpg',
                    'alt' => 'Don\'t go chasing waterfalls',
                    'fullWidth' => false,
                    'width' => '640px',
                    'height' => '425px',
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
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'button',
                    'text' => 'View NZ Photo Gallery',
                    'url' => 'http://example.org',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#f16161',
                        'borderColor' => '#ffffff',
                        'borderWidth' => '3px',
                        'borderRadius' => '5px',
                        'borderStyle' => 'solid',
                        'width' => '288px',
                        'lineHeight' => '48px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Verdana',
                        'fontSize' => '18px',
                        'fontWeight' => 'normal',
                        'textAlign' => 'center',
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
                        'padding' => '23px',
                        'borderStyle' => 'double',
                        'borderWidth' => '3px',
                        'borderColor' => '#aaaaaa',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'text',
                    'text' => '<h2><em>Here\'s our top recommendations in Rotarua</em></h2>',
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
          5 => array(
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
                    'link' => 'http://example.org',
                    'src' => $this->template_image_url . '/glow-worms.jpg',
                    'alt' => 'Glow worms, Waitomo Caves',
                    'fullWidth' => true,
                    'width' => '640px',
                    'height' => '428px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'text',
                    'text' => '<p><em><a href="http://www.waitomo.com/Waitomo-Glowworm-Caves/Pages/default.aspx"><strong>Waitomo Glow Worm Caves</strong></a></em></p>',
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
                    'type' => 'image',
                    'link' => 'http://example.org',
                    'src' => $this->template_image_url . '/luge.jpg',
                    'alt' => 'luge',
                    'fullWidth' => false,
                    'width' => '375px',
                    'height' => '500px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'text',
                    'text' => '<p><em><strong><a href="http://www.skyline.co.nz/rotorua/ssr_luge/">Luge!</a></strong></em></p>',
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
                    'type' => 'image',
                    'link' => 'http://example.org',
                    'src' => $this->template_image_url . '/holiday-park.jpg',
                    'alt' => 'holiday-park',
                    'fullWidth' => true,
                    'width' => '640px',
                    'height' => '425px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'text',
                    'text' => '<p><em><strong><a href="http://rotoruathermal.co.nz/">Roturua Thermal Holiday Park</a></strong></em></p>',
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
                        'height' => '21px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'text',
                    'text' => '<p>Morbi ex diam, venenatis a efficitur et, iaculis at nibh. Ut rhoncus, lacus vel fermentum aliquam, mi arcu pharetra metus, quis ullamcorper tortor ligula in diam. Fusce mi elit, finibus at lectus non, pulvinar fringilla risus. Integer porta vel quam et fringilla.</p>
                      <p></p>
                      <p>Morbi sit amet,</p>
                      <p>Jane &amp; Steven</p>',
                  ),
                ),
              ),
            ),
          ),
          7 => array(
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
                        'padding' => '13px',
                        'borderStyle' => 'dotted',
                        'borderWidth' => '2px',
                        'borderColor' => '#aaaaaa',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'header',
                    'text' => 'Display problems?&nbsp;<a href="[link:newsletter_view_in_browser_url]">Open this email in your web browser</a>',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                      ),
                      'text' => array(
                        'fontColor' => '#222222',
                        'fontFamily' => 'Courier New',
                        'fontSize' => '12px',
                        'textAlign' => 'center',
                      ),
                      'link' => array(
                        'fontColor' => '#343434',
                        'textDecoration' => 'underline',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'footer',
                    'text' => '<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage subscription</a></p>',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                      ),
                      'text' => array(
                        'fontColor' => '#343434',
                        'fontFamily' => 'Courier New',
                        'fontSize' => '12px',
                        'textAlign' => 'center',
                      ),
                      'link' => array(
                        'fontColor' => '#343434',
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
          'fontColor' => '#343434',
          'fontFamily' => 'Courier New',
          'fontSize' => '16px',
        ),
        'h1' => array(
          'fontColor' => '#180d6b',
          'fontFamily' => 'Trebuchet MS',
          'fontSize' => '26px',
        ),
        'h2' => array(
          'fontColor' => '#180d6b',
          'fontFamily' => 'Trebuchet MS',
          'fontSize' => '22px',
        ),
        'h3' => array(
          'fontColor' => '#343434',
          'fontFamily' => 'Trebuchet MS',
          'fontSize' => '18px',
        ),
        'link' => array(
          'fontColor' => '#f16161',
          'textDecoration' => 'underline',
        ),
        'wrapper' => array(
          'backgroundColor' => '#daf3ff',
        ),
        'body' => array(
          'backgroundColor' => '#ffffff',
        ),
      ),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/travel-nomads.jpg';
  }

}
