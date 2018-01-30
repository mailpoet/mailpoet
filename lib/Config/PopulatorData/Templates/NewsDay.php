<?php

namespace MailPoet\Config\PopulatorData\Templates;

class NewsDay {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
     $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/news_day';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("News Day", 'mailpoet'),
      'description' => __("Media ready template.", 'mailpoet'),
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
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#f2f2f2',
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/News-Outlet-Title-2.jpg',
                    'alt' => 'News-Outlet-Title-2',
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
          1 => array(
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
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '25px',
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
                    'type' => 'text',
                    'text' => '<h1 style="text-align: left;"><span style="color: #2ca5d2;"><strong>Top Story</strong></span></h1>',
                  ),
                  1 => array(
                    'type' => 'text',
                    'text' => '<h2 style="text-align: left;" data-post-id="1991"><strong>Plasma jet engines that could take you from the ground to space</strong></h2>',
                  ),
                  2 => array(
                    'type' => 'image',
                    'link' => 'http://mailpoet.info/plasma-jet-engines-that-could-take-you-from-the-ground-to-space/',
                    'src' => $this->template_image_url . '/plasma-stingray111-800x533.jpg',
                    'alt' => 'Plasma jet engines that could take you from the ground to space',
                    'fullWidth' => false,
                    'width' => 660,
                    'height' => 440,
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  3 => array(
                    'type' => 'text',
                    'text' => '<p class="mailpoet_wp_post">FORGET fuel-powered jet engines. We’re on the verge of having aircraft that can fly from the ground up to the edge of space using air and electricity alone. Traditional jet engines create thrust by mixing compressed air with fuel and igniting it. The burning mixture expands rapidly and is blasted out of the back of the engine, pushing it forwards. &hellip;</p><p><a href="http://mailpoet.info/plasma-jet-engines-that-could-take-you-from-the-ground-to-space/">Read More</a></p>',
                  ),
                  4 => array(
                    'type' => 'divider',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'padding' => '13px',
                        'borderStyle' => 'solid',
                        'borderWidth' => '1px',
                        'borderColor' => '#aaaaaa',
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
                  6 => array(
                    'type' => 'text',
                    'text' => '<h3><span style="color: #2ca5d2;"><strong>Popular Posts Today</strong></span></h3>',
                  ),
                  7 => array(
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
                    'type' => 'text',
                    'text' => '<h3 style="text-align: left; font-size: 18px; line-height: 1.4;" data-post-id="1997"><strong>Cutting through the smog: What to do to fight air pollution</strong></h3>
                      <p class="mailpoet_wp_post">Tackling our air problems starts with traffic control, but individual action to reduce energy use and intensive farming would also help clean our air.</p>
                      <p><a href="http://mailpoet.info/cutting-through-the-smog-what-to-do-to-fight-air-pollution/">Read More</a></p>',
                  ),
                  1 => array(
                    'type' => 'divider',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'padding' => '13px',
                        'borderStyle' => 'solid',
                        'borderWidth' => '1px',
                        'borderColor' => '#aaaaaa',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'text',
                    'text' => '<h3 style="text-align: left; font-size: 18px; line-height: 1.4;" data-post-id="1994"><strong>Ladybird&rsquo;s transparent shell reveals how it&nbsp;moves</strong></h3>
                      <p class="mailpoet_wp_post">They certainly know how to fold. A see-through artificial wing case has been used to watch for the first time as ladybirds put away their wings after flight.</p>
                      <p><a href="http://mailpoet.info/ladybirds-transparent-shell-reveals-how-it-folds-its-wings/">Read More</a></p>',
                  ),
                  3 => array(
                    'type' => 'divider',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'padding' => '13px',
                        'borderStyle' => 'solid',
                        'borderWidth' => '1px',
                        'borderColor' => '#aaaaaa',
                      ),
                    ),
                  ),
                  4 => array(
                    'type' => 'text',
                    'text' => '<h3 style="text-align: left; font-size: 18px; line-height: 1.4;" data-post-id="1938"><strong>Brazil&rsquo;s history-making Hurricane</strong></h3>
                      <p class="mailpoet_wp_post">Jairzinho has just made history. In claiming the fourth goal of an unforgettable 1970 FIFA World Cup Mexico&trade; Final against Italy, he has maintained his record of scoring in every one of Brazil&rsquo;s matches en route to the Trophy.</p>
                      <p><a href="http://mailpoet.info/brazils-history-making-hurricane/">Read More</a></p>',
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
                    'text' => '<h3 style="text-align: left; font-size: 18px; line-height: 1.4;" data-post-id="1935"><strong>Iceland&rsquo;s dentist-coach defying convention and expectations</strong></h3>
                      <p class="mailpoet_wp_post">As Iceland&rsquo;s key matches loom, with kick-off just a couple of hours away, you will find their national coach in the pub. This may seem unusual...</p>
                      <p><a href="http://mailpoet.info/icelands-dentist-coach-defying-convention-and-expectations/">Read More</a></p>',
                  ),
                  1 => array(
                    'type' => 'divider',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'padding' => '13px',
                        'borderStyle' => 'solid',
                        'borderWidth' => '1px',
                        'borderColor' => '#aaaaaa',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'text',
                    'text' => '<h3 style="text-align: left; font-size: 18px; line-height: 1.4;" data-post-id="1932"><strong>Impact and legacy of 2018 FIFA World Cup Russia</strong></h3>
                      <p class="mailpoet_wp_post">Organising a FIFA World Cup&trade; in a sustainable manner is a major challenge. The scale of the event inevitably has an impact on the Host Country.&nbsp;</p>
                      <p><a href="http://mailpoet.info/impact-and-legacy-of-2018-fifa-world-cup-russia-facts-and-figures/">Read More</a></p>',
                  ),
                  3 => array(
                    'type' => 'divider',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'padding' => '13px',
                        'borderStyle' => 'solid',
                        'borderWidth' => '1px',
                        'borderColor' => '#aaaaaa',
                      ),
                    ),
                  ),
                  4 => array(
                    'type' => 'text',
                    'text' => '<h3 style="text-align: left; font-size: 18px; line-height: 1.4;" data-post-id="1929"><strong>Lineker&rsquo;s life-changing treble</strong></h3>
                      <p class="mailpoet_wp_post">Given that he won the Golden Boot in his first and came within a whisker of the Final in his second, one might expect Gary Lineker to have a tough time picking his FIFA World Cup&trade; highlight. Yet the man who scored ten times...</p>
                      <p><a href="http://mailpoet.info/linekers-life-changing-treble/">Read More</a></p>',
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
                        'height' => '40px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#f2f2f2',
                        'height' => '40px',
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
                    'type' => 'footer',
                    'text' => '<p><strong>NewsDay</strong></p>
                      <p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage subscription</a><br /><br /></p>
                      <p></p>',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                      ),
                      'text' => array(
                        'fontColor' => '#222222',
                        'fontFamily' => 'Arial',
                        'fontSize' => '12px',
                        'textAlign' => 'left',
                      ),
                      'link' => array(
                        'fontColor' => '#6cb7d4',
                        'textDecoration' => 'underline',
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
                    'type' => 'social',
                    'iconSet' => 'circles',
                    'icons' => array(
                      0 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://www.facebook.com',
                        'image' => $this->social_icon_url . '/03-circles/Facebook.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                      1 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com',
                        'image' => $this->social_icon_url . '/03-circles/Twitter.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                      2 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'youtube',
                        'link' => 'http://www.youtube.com',
                        'image' => $this->social_icon_url . '/03-circles/Youtube.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Youtube',
                      ),
                      3 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'instagram',
                        'link' => 'http://instagram.com',
                        'image' => $this->social_icon_url . '/03-circles/Instagram.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Instagram',
                      ),
                      4 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'linkedin',
                        'link' => 'http://www.linkedin.com',
                        'image' => $this->social_icon_url . '/03-circles/LinkedIn.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'LinkedIn',
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
                        'backgroundColor' => '#f2f2f2',
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
          'fontColor' => '#000000',
          'fontFamily' => 'Arial',
          'fontSize' => '13px',
        ),
        'h1' => array(
          'fontColor' => '#111111',
          'fontFamily' => 'Arial',
          'fontSize' => '30px',
        ),
        'h2' => array(
          'fontColor' => '#222222',
          'fontFamily' => 'Arial',
          'fontSize' => '24px',
        ),
        'h3' => array(
          'fontColor' => '#333333',
          'fontFamily' => 'Arial',
          'fontSize' => '22px',
        ),
        'link' => array(
          'fontColor' => '#2ca5d2',
          'textDecoration' => 'underline',
        ),
        'wrapper' => array(
          'backgroundColor' => '#ffffff',
        ),
        'body' => array(
          'backgroundColor' => '#f2f2f2',
        ),
      ),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/news-day.jpg';
  }

}
