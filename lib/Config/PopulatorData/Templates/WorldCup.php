<?php

namespace MailPoet\Config\PopulatorData\Templates;

class WorldCup {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
     $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/world_cup';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("World Cup", 'mailpoet'),
      'description' => __("Always a winner.", 'mailpoet'),
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
                'backgroundColor' => '#222222',
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
                        'height' => '20px',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Sports-Football-Header.png',
                    'alt' => 'Sports-Football-Header',
                    'fullWidth' => true,
                    'width' => '1280px',
                    'height' => '220px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Sports-Football-Divider-1.png',
                    'alt' => 'Sports-Football-Divider-1',
                    'fullWidth' => true,
                    'width' => '1280px',
                    'height' => '50px',
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
                'backgroundColor' => '#da6110',
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
                        'height' => '20px',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'text',
                    'text' => '<p><strong><span style="color: #ffffff; font-size: 14px;">Issue #1</span></strong></p>',
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
                    'text' => '<p style="text-align: right;"><a href="[link:newsletter_view_in_browser_url]" target="_blank" style="color: #ffffff; font-size: 14px; text-align: center;">View In Browser</a></p>
                                        <p style="text-align: right;"><span style="color: #ffffff; text-align: start;">Monday 1st January 2017</span></p>',
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
                        'backgroundColor' => '#da6110',
                        'height' => '20px',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Sports-Football-Header-1.png',
                    'alt' => 'Sports-Football-Header',
                    'fullWidth' => true,
                    'width' => '1280px',
                    'height' => '580px',
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
                    'type' => 'text',
                    'text' => '<h2 style="text-align: left;"><strong>Welcome Back!</strong></h2>
                                      <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam consequat lorem at est congue, non consequat lacus iaculis. Integer euismod mauris velit, vel ultrices nibh bibendum quis. Donec eget fermentum magna.</p>
                                      <p></p>
                                      <p>Nullam congue dui lectus, quis pellentesque orci placerat eu. Fusce semper neque a mi aliquet vulputate sed sit amet nisi. Etiam sed nisl nec orci pretium lacinia eget in turpis. Maecenas in posuere justo. Vestibulum et sapien vestibulum, imperdiet neque in, maximus velit.</p>
                                      <p></p>
                                      <p>Proin dignissim elit magna, viverra scelerisque libero vehicula sed</p>',
                  ),
                  array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Sports-Football-Divider-3.png',
                    'alt' => 'Sports-Football-Divider-3',
                    'fullWidth' => true,
                    'width' => '1280px',
                    'height' => '50px',
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
                        'backgroundColor' => '#efefef',
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
                'backgroundColor' => '#efefef',
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
                    'text' => '<h2 style="padding-bottom: 0;"><span style="font-weight: 600;">Latest News</span></h2>',
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
                    'type' => 'button',
                    'text' => 'View All News',
                    'url' => '',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#d35400',
                        'borderColor' => '#d35400',
                        'borderWidth' => '1px',
                        'borderRadius' => '5px',
                        'borderStyle' => 'solid',
                        'width' => '110px',
                        'lineHeight' => '36px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Tahoma',
                        'fontSize' => '14px',
                        'fontWeight' => 'normal',
                        'textAlign' => 'right',
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
                'backgroundColor' => '#efefef',
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
                'backgroundColor' => '#efefef',
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
                    'link' => 'http://mailpoet.info/brazils-history-making-hurricane/',
                    'src' => $this->template_image_url . '/2865897_full-lnd.jpg',
                    'alt' => 'Brazil’s history-making Hurricane',
                    'fullWidth' => false,
                    'width' => 652,
                    'height' => 366,
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'text',
                    'text' => '<h3 style="text-align: left;" data-post-id="1938"><strong>Brazil&rsquo;s history-making Hurricane</strong></h3>
                                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam consequat lorem at est congue, non consequat lacus iaculis. Integer euismod mauris velit, vel ultrices nibh bibendum quis. Donec eget fermentum magna. Nullam congue dui lectus, quis pellentesque orci placerat eu. Fusce semper neque a mi aliquet vulputate sed sit amet nisi...</p>
                                            <p><a href="http://mailpoet.info/brazils-history-making-hurricane/">Read More</a></p>',
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
                'backgroundColor' => '#efefef',
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
                    'link' => 'http://mailpoet.info/icelands-dentist-coach-defying-convention-and-expectations/',
                    'src' => $this->template_image_url . '/2866107_full-lnd.jpg',
                    'alt' => 'Iceland’s dentist-coach defying convention and expectations',
                    'fullWidth' => false,
                    'width' => 652,
                    'height' => 366,
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'text',
                    'text' => '<h3><strong>Iceland&rsquo;s dentist-coach defying convention and expectations</strong></h3>
                                          <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam consequat lorem at est congue, non consequat lacus iaculis. Integer euismod mauris velit...</p>
                                          <p><a href="http://mailpoet.info/icelands-dentist-coach-defying-convention-and-expectations/">Read More</a></p>',
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
                    'link' => 'http://mailpoet.info/impact-and-legacy-of-2018-fifa-world-cup-russia-facts-and-figures/',
                    'src' => $this->template_image_url . '/2709222_full-lnd.jpg',
                    'alt' => 'Impact and legacy of 2018 FIFA World Cup Russia: facts and figures',
                    'fullWidth' => false,
                    'width' => 652,
                    'height' => 366,
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'text',
                    'text' => '<h3 style="text-align: left;" data-post-id="1932"><strong>Impact and legacy of 2018 FIFA World Cup Russia: facts and figures</strong></h3>
                                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam consequat lorem at est congue, non consequat lacus iaculis. Integer euismod...</p>
                                        <p><a href="http://mailpoet.info/impact-and-legacy-of-2018-fifa-world-cup-russia-facts-and-figures/">Read More</a></p>',
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
                    'link' => 'http://mailpoet.info/linekers-life-changing-treble/',
                    'src' => $this->template_image_url . '/2867790_full-lnd.jpg',
                    'alt' => 'Lineker’s life-changing treble',
                    'fullWidth' => false,
                    'width' => 652,
                    'height' => 366,
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),

                  array(
                    'type' => 'text',
                    'text' => '<h3 style="text-align: left;" data-post-id="1929"><strong>Lineker&rsquo;s life-changing treble</strong></h3>
                                      <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam consequat lorem at est congue, non consequat lacus iaculis. Integer euismod mauris velit&nbsp;<span style="background-color: inherit;">consequat lorem at est congue...</span></p>
                                      <p><a href="http://mailpoet.info/linekers-life-changing-treble/">Read More</a></p>',
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
                'backgroundColor' => '#f8f8f8',
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
                        'backgroundColor' => '#efefef',
                        'height' => '20px',
                      ),
                    ),
                  ),
                  array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Sports-Football-Divider-2.png',
                    'alt' => 'Sports-Football-Divider-2',
                    'fullWidth' => true,
                    'width' => '1280px',
                    'height' => '50px',
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
                'backgroundColor' => '#222222',
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
                    'src' => $this->template_image_url . '/Sports-Football-Footer-1.png',
                    'alt' => 'Sports-Football-Footer',
                    'fullWidth' => true,
                    'width' => '1280px',
                    'height' => '500px',
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
                        'backgroundColor' => '#da6110',
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
                'backgroundColor' => '#da6110',
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
                    'type' => 'social',
                    'iconSet' => 'full-symbol-grey',
                    'icons' => array(
                      array(
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://www.facebook.com',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Facebook.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                      array(
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Twitter.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                      array(
                        'type' => 'socialIcon',
                        'iconType' => 'youtube',
                        'link' => 'http://www.youtube.com',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Youtube.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Youtube',
                      ),
                      array(
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
          array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => '#b55311',
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
                        'backgroundColor' => '#da6110',
                        'height' => '20px',
                      ),
                    ),
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
                  array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Sports-Football-Logo-Small.png',
                    'alt' => 'Sports-Football-Logo-Small',
                    'fullWidth' => false,
                    'width' => '772px',
                    'height' => '171px',
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
                        'height' => '20px',
                      ),
                    ),
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
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#da6110',
                        'height' => '20px',
                      ),
                    ),
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
                  array(
                    'type' => 'footer',
                    'text' => '<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage subscription</a><br />Add your postal address here!</p>',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                      ),
                      'text' => array(
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Tahoma',
                        'fontSize' => '12px',
                        'textAlign' => 'right',
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
          'fontColor' => '#000000',
          'fontFamily' => 'Tahoma',
          'fontSize' => '14px',
        ),
        'h1' => array(
          'fontColor' => '#111111',
          'fontFamily' => 'Tahoma',
          'fontSize' => '30px',
        ),
        'h2' => array(
          'fontColor' => '#da6110',
          'fontFamily' => 'Tahoma',
          'fontSize' => '24px',
        ),
        'h3' => array(
          'fontColor' => '#333333',
          'fontFamily' => 'Tahoma',
          'fontSize' => '18px',
        ),
        'link' => array(
          'fontColor' => '#da6110',
          'textDecoration' => 'underline',
        ),
        'wrapper' => array(
          'backgroundColor' => '#ffffff',
        ),
        'body' => array(
          'backgroundColor' => '#222222',
        ),
      ),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/world-cup.jpg';
  }

}
