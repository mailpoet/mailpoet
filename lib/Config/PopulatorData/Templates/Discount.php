<?php

namespace MailPoet\Config\PopulatorData\Templates;

class Discount {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
     $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/discount';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Discount", 'mailpoet'),
      'description' => __("A useful layout for a simple discount email.", 'mailpoet'),
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
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'image',
                    'link' => 'http://example.org',
                    'src' => $this->template_image_url . '/bicycle-header3.png',
                    'alt' => 'bicycle-header3',
                    'fullWidth' => false,
                    'width' => '423px',
                    'height' => '135px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'text',
                    'text' => '<p></p>
                        <p>Hi&nbsp;[subscriber:firstname | default:reader]</p>
                        <p class=""></p>
                        <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Donec ut venenatis ipsum. Etiam efficitur magna a convallis consectetur.&nbsp;Nunc dapibus cursus mauris vel sollicitudin. Etiam magna libero, posuere ac nulla nec, iaculis pulvinar arcu.</p>',
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
                'backgroundColor' => '#ebdddd',
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
                        'padding' => '16px',
                        'borderStyle' => 'dashed',
                        'borderWidth' => '2px',
                        'borderColor' => '#9a5fa1',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'text',
                    'text' => '<h1 style="text-align: center;"><em><strong>15% odio felis fringilla eget enim</strong></em></h1>',
                  ),
                  2 => array(
                    'type' => 'text',
                    'text' => '<h2 style="text-align: center;">FUSCE LOBORTIS<strong>: WELOVEMAILPOET</strong></h2>',
                  ),
                  3 => array(
                    'type' => 'divider',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'padding' => '16px',
                        'borderStyle' => 'dashed',
                        'borderWidth' => '2px',
                        'borderColor' => '#9a5fa1',
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
                    'type' => 'button',
                    'text' => 'SHOP NOW',
                    'url' => 'http://example.org',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#9a5fa1',
                        'borderColor' => '#854f8b',
                        'borderWidth' => '3px',
                        'borderRadius' => '5px',
                        'borderStyle' => 'solid',
                        'width' => '288px',
                        'lineHeight' => '50px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Verdana',
                        'fontSize' => '18px',
                        'fontWeight' => 'normal',
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'text',
                    'text' => '<h1 style="text-align: center;"><strong><em>Use your discount on these great&nbsp;products...</em></strong></h1>',
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
                    'type' => 'image',
                    'link' => 'http://example.org',
                    'src' => $this->template_image_url . '/red-icycle-2.jpg',
                    'alt' => 'red-icycle',
                    'fullWidth' => false,
                    'width' => '558px',
                    'height' => '399px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'text',
                    'text' => '<h3 style="text-align: center;">Lovely Red Bicycle</h3>
                                    <p>Quisque nec vulputate velit, non sagittis ex.&nbsp;Suspendisse ligula urna, tempus sed iaculis sit amet, convallis at arcu.</p>
                                    <h3 style="text-align: center;"><strong><span style="color: #488e88;">$289.99</span></strong></h3>',
                  ),
                  2 => array(
                    'type' => 'button',
                    'text' => 'Tempus',
                    'url' => 'http://example.org',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#9a5fa1',
                        'borderColor' => '#854f8b',
                        'borderWidth' => '3px',
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
                    'src' => $this->template_image_url . '/orange-bicycle.jpg',
                    'alt' => 'orange-bicycle',
                    'fullWidth' => false,
                    'width' => '639px',
                    'height' => '457px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'text',
                    'text' => '<h3 style="text-align: center;">Little Orange Bicycle</h3>
                                    <p>Praesent molestie mollis sapien vel dignissim. Maecenas ultrices, odio eget dapibus iaculis, ligula ex aliquet leo.</p>
                                    <h3 style="line-height: 22.4px; text-align: center;"><span style="color: #488e88;"><strong>$209.99</strong></span></h3>',
                  ),
                  2 => array(
                    'type' => 'button',
                    'text' => 'Tempus',
                    'url' => 'http://example.org',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#9a5fa1',
                        'borderColor' => '#854f8b',
                        'borderWidth' => '3px',
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
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '22px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'divider',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'padding' => '20px',
                        'borderStyle' => 'solid',
                        'borderWidth' => '1px',
                        'borderColor' => '#9e9e9e',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'text',
                    'text' => '<p><em>Diam et vestibulum facilisis:</em></p>
                                    <ul>
                                    <li>Massa justo tincidunt magna, a volutpat dolor leo vel mi.</li>
                                    <li>Curabitur ornare tellus libero, nec porta dolor elementum et.</li>
                                    <li>Vestibulum sodales congue ex quis euismod.</li>
                                    </ul>',
                  ),
                  3 => array(
                    'type' => 'social',
                    'iconSet' => 'grey',
                    'icons' => array(
                      0 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://example.com',
                        'image' => $this->social_icon_url . '/02-grey/Facebook.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                      1 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://example.com',
                        'image' => $this->social_icon_url . '/02-grey/Twitter.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                    ),
                  ),
                  4 => array(
                    'type' => 'footer',
                    'text' => '<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage subscription</a></p><p>1 Store Street, Shopville, CA 1345</p>',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                      ),
                      'text' => array(
                        'fontColor' => '#343434',
                        'fontFamily' => 'Verdana',
                        'fontSize' => '12px',
                        'textAlign' => 'center',
                      ),
                      'link' => array(
                        'fontColor' => '#488e88',
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
          'fontColor' => '#343434',
          'fontFamily' => 'Verdana',
          'fontSize' => '14px',
        ),
        'h1' => array(
          'fontColor' => '#488e88',
          'fontFamily' => 'Trebuchet MS',
          'fontSize' => '22px',
        ),
        'h2' => array(
          'fontColor' => '#9a5fa1',
          'fontFamily' => 'Verdana',
          'fontSize' => '24px',
        ),
        'h3' => array(
          'fontColor' => '#9a5fa1',
          'fontFamily' => 'Trebuchet MS',
          'fontSize' => '18px',
        ),
        'link' => array(
          'fontColor' => '#488e88',
          'textDecoration' => 'underline',
        ),
        'wrapper' => array(
          'backgroundColor' => '#afe3de',
        ),
        'body' => array(
          'backgroundColor' => '#afe3de',
        ),
      ),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/discount.jpg';
  }

}