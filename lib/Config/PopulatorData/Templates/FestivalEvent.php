<?php

namespace MailPoet\Config\PopulatorData\Templates;

class FestivalEvent {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
     $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/festival_event';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Festival Event", 'mailpoet'),
      'description' => __("A colourful festival event template.", 'mailpoet'),
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
                        'backgroundColor' => '#0a5388',
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/festival-header.jpg',
                    'alt' => 'festival-header',
                    'fullWidth' => true,
                    'width' => '1320px',
                    'height' => '879px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '36px',
                      ),
                    ),
                  ),
                  3 => array(
                    'type' => 'text',
                    'text' => '<h1 style="text-align: center;">Pack your glowsticks, <br />Boomfest is back!&nbsp;</h1>
                        <p></p>
                        <p style="text-align: center;">Duis tempor nisl in risus hendrerit venenatis. <br />Curabitur ornare venenatis nisl non ullamcorper. </p>',
                  ),
                  4 => array(
                    'type' => 'button',
                    'text' => 'Duis id tincidunt',
                    'url' => '',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#0a5388',
                        'borderColor' => '#0074a2',
                        'borderWidth' => '0px',
                        'borderRadius' => '5px',
                        'borderStyle' => 'solid',
                        'width' => '260px',
                        'lineHeight' => '50px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Courier New',
                        'fontSize' => '18px',
                        'fontWeight' => 'bold',
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  5 => array(
                    'type' => 'text',
                    'text' => '<p style="text-align: center;">Maecenas scelerisque nisi sit amet metus efficitur dapibus!&nbsp;<br />Ut eros risus, facilisis ac aliquet vel, posuere ut urna.</p>',
                  ),
                  6 => array(
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
                        'iconType' => 'instagram',
                        'link' => 'http://instagram.com',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Instagram.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Instagram',
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
                    'type' => 'divider',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'padding' => '13px',
                        'borderStyle' => 'dashed',
                        'borderWidth' => '2px',
                        'borderColor' => '#ffffff',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '28px',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'text',
                    'text' => '<h2 style="text-align: center;">Confirmed Lineup</h2>',
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
                    'type' => 'text',
                    'text' => '<h3><em><span style="color: #bae2ff;">Main Stage</span></em></h3><p>Quisque libero<br />Nulla convallis<br />Vestibulum Ornare<br />Consectetur Odio</p>',
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
                    'text' => '<h3><em><span style="color: #bae2ff;">New Acts Stage</span></em></h3><p>Nulla interdum<br />Massa nec<br />Pharetra<br />Varius</p>',
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
                    'text' => '<h3><em><span style="color: #bae2ff;">Comedy Stage</span></em></h3><p>In pulvinar<br />Risus sed<br />Condimentum<br />Feugiat</p>',
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
                    'type' => 'divider',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'padding' => '13px',
                        'borderStyle' => 'dashed',
                        'borderWidth' => '2px',
                        'borderColor' => '#ffffff',
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
                  3 => array(
                    'type' => 'text',
                    'text' => '<h2 style="text-align: center;">New to the festival this year</h2>',
                  ),
                  4 => array(
                    'type' => 'divider',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'padding' => '9px',
                        'borderStyle' => 'dashed',
                        'borderWidth' => '2px',
                        'borderColor' => '#ffffff',
                      ),
                    ),
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/street-food.jpg',
                    'alt' => 'street food',
                    'fullWidth' => true,
                    'width' => '499px',
                    'height' => '750px',
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
                    'text' => '<h3>Award-winning Street Food</h3><p>Nullam pharetra lectus id porta pulvinar. Proin ac massa nibh. Nullam ac mi pharetra, lobortis nunc et, placerat leo. Mauris eu feugiat elit. Pellentesque eget turpis eu diam vehicula convallis non <a href="http://www.mailpoet.com">luctus enim.</a></p>',
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
                    'link' => '',
                    'src' => $this->template_image_url . '/happy.jpeg',
                    'alt' => 'happy',
                    'fullWidth' => true,
                    'width' => '499px',
                    'height' => '750px',
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
                    'text' => '<h3>Prepare to&nbsp;dazzle with our Glitter Run</h3><p>Donec quis orci at metus finibus tincidunt. Sed vel urna sed urna maximus congue eu et turpis. Nulla tempus hendrerit justo eget molestie. Vivamus quis molestie lacus. Donec commodo odio a nisi feugiat, vitae egestas mi.</p>',
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
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'divider',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'padding' => '13px',
                        'borderStyle' => 'dashed',
                        'borderWidth' => '2px',
                        'borderColor' => '#ffffff',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'footer',
                    'text' => '<p>Mauris tristique ultricies ullamcorper. <br />Don\'t want to hear from us?&nbsp;<a href="[link:subscription_unsubscribe_url]">Unsubscribe</a></p><p></p><p>Add your postal address here.&nbsp;</p>',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                      ),
                      'text' => array(
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Courier New',
                        'fontSize' => '13px',
                        'textAlign' => 'center',
                      ),
                      'link' => array(
                        'fontColor' => '#ffffff',
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
          'fontColor' => '#ffffff',
          'fontFamily' => 'Courier New',
          'fontSize' => '16px',
        ),
        'h1' => array(
          'fontColor' => '#ffffff',
          'fontFamily' => 'Georgia',
          'fontSize' => '36px',
        ),
        'h2' => array(
          'fontColor' => '#ffffff',
          'fontFamily' => 'Georgia',
          'fontSize' => '26px',
        ),
        'h3' => array(
          'fontColor' => '#ffffff',
          'fontFamily' => 'Georgia',
          'fontSize' => '24px',
        ),
        'link' => array(
          'fontColor' => '#ffffff',
          'textDecoration' => 'underline',
        ),
        'wrapper' => array(
          'backgroundColor' => '#8d062b',
        ),
        'body' => array(
          'backgroundColor' => '#0a5388',
        ),
      ),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/festival-event.jpg';
  }

}