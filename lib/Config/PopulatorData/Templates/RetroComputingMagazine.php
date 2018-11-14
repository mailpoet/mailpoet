<?php

namespace MailPoet\Config\PopulatorData\Templates;

class RetroComputingMagazine {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/retro_computing_magazine';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Retro Computing Magazine", 'mailpoet'),
      'categories' => json_encode(array('standard', 'all')),
      'readonly' => 1,
      'thumbnail' => $this->getThumbnail(),
      'body' => json_encode($this->getBody()),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/retro_computing_magazine.jpg';
  }

  private function getBody() {
    return array (
      'content' =>
        array (
          'type' => 'container',
          'orientation' => 'vertical',
          'styles' =>
            array (
              'block' =>
                array (
                  'backgroundColor' => 'transparent',
                ),
            ),
          'blocks' =>
            array (
              0 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#4473a1',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#008282',
                                          'height' => '40px',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
              1 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#f8f8f8',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Windows94-Header.png',
                                  'alt' => 'Windows94-Header',
                                  'fullWidth' => true,
                                  'width' => '1280px',
                                  'height' => '740px',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
              2 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#ffffff',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'header',
                                  'text' => '<p><span style="color: #ffffff;">Display problems? <a href="[link:newsletter_view_in_browser_url]" style="color: #ffffff;">Open this email in your web browser.</a></span></p>',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#008282',
                                        ),
                                      'text' =>
                                        array (
                                          'fontColor' => '#222222',
                                          'fontFamily' => 'Courier New',
                                          'fontSize' => '12px',
                                          'textAlign' => 'left',
                                        ),
                                      'link' =>
                                        array (
                                          'fontColor' => '#6cb7d4',
                                          'textDecoration' => 'underline',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => 'transparent',
                                          'height' => '30px',
                                        ),
                                    ),
                                ),
                              2 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<h1 style="text-align: left;"><strong>We\'re upgrading!</strong></h1>
<p><span>Lorem ipsum dolor sit amet, consectetur adipiscing elit. In elementum nunc vel est congue, a venenatis nunc aliquet. Curabitur luctus, nulla et dignissim elementum, ipsum eros fermentum nulla, non cursus eros mi eu velit. Nunc ex nibh, porta vulputate pharetra ac, placerat sed orci. Etiam enim enim, aliquet nec ligula in, ultrices iaculis dolor. Suspendisse potenti. Praesent fringilla augue ut lorem mattis, vitae fringilla nunc faucibus. </span></p>
<p><span></span></p>
<p><span>Quisque in leo felis. Etiam at libero et enim tincidunt scelerisque. Ut felis lectus, imperdiet quis justo quis, elementum sagittis tellus. Sed elementum, lacus at iaculis vestibulum, nunc leo gravida nisi, sed dapibus nisi odio ac ex. Aliquam id arcu dictum, cursus quam id, eleifend libero.</span></p>',
                                ),
                              3 =>
                                array (
                                  'type' => 'divider',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => 'transparent',
                                          'padding' => '13px',
                                          'borderStyle' => 'ridge',
                                          'borderWidth' => '3px',
                                          'borderColor' => '#aaaaaa',
                                        ),
                                    ),
                                ),
                              4 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => 'transparent',
                                          'height' => '20px',
                                        ),
                                    ),
                                ),
                              5 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<h1><strong>Latest News</strong></h1>',
                                ),
                            ),
                        ),
                    ),
                ),
              3 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => 'transparent',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<h3 style="text-align: left;"><strong>What is it like to use a Windows 98 PC in 2017?</strong></h3>',
                                ),
                              1 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<p class="mailpoet_wp_post"><span>Computers are much more advanced than they were even a few years ago, but of course we all like to complain about the dumb things they sometimes do. It&rsquo;s easy to forget how clunky things used to be, though...</span></p>
<p><a href="http://mailpoet.info/odds-on-10-science-breakthroughs-you-can-bet-on/">Read more</a></p>',
                                ),
                            ),
                        ),
                      1 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<h3 style="text-align: left;"><strong>Windows 95 still finds life online</strong></h3>',
                                ),
                              1 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<p class="mailpoet_wp_post">Microsoft&rsquo;s Windows 95 has reached the ripe old age of 22 this year and to commemorate this milestone, TheNextWeb goes into some details about the operating system that users may have missed over the years...</p>
<p><a href="http://mailpoet.info/brazils-history-making-hurricane/">Read more</a></p>',
                                ),
                            ),
                        ),
                      2 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<h3 style="text-align: left;"><strong>New Sinclair ZX Spectrum Fully Funded</strong></h3>',
                                ),
                              1 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<p class="mailpoet_wp_post">The new Sinclair ZX Spectrum Next home computer which was launched on Kickstarter to mark the 35th birthday of the original Spectrum produced by Sinclair Research has been fully funded in less than 48 hours...</p>
<p><a href="http://mailpoet.info/cutting-through-the-smog-what-to-do-to-fight-air-pollution/">Read more</a></p>',
                                ),
                            ),
                        ),
                    ),
                ),
              4 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => 'transparent',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => 'transparent',
                                          'height' => '35px',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#008282',
                                          'height' => '50px',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
              5 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#008282',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<h2><strong><span style="color: #ffffff;">Did you know?</span></strong></h2>
<p><span style="color: #ffffff;">At the time of creation and development, the microcomputers in Japan were not powerful enough to handle the complex tasks related to the design and programming of Space Invaders. Nishikado then designed his own hardware and developmental tools to make the game a reality.</span></p>
<p><strong><span style="color: #ffffff;"></span></strong></p>',
                                ),
                            ),
                        ),
                      1 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Windows94-Today.png',
                                  'alt' => 'Windows94-Today',
                                  'fullWidth' => false,
                                  'width' => '364px',
                                  'height' => '291px',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
              6 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => 'transparent',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#008282',
                                          'height' => '20px',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
              7 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#f8f8f8',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
                          'orientation' => 'vertical',
                          'styles' =>
                            array (
                              'block' =>
                                array (
                                  'backgroundColor' => 'transparent',
                                ),
                            ),
                          'blocks' =>
                            array (
                              0 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => 'transparent',
                                          'height' => '20px',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<p style="text-align: center;"><strong>Let\'s get social!</strong></p>',
                                ),
                              2 =>
                                array (
                                  'type' => 'social',
                                  'iconSet' => 'grey',
                                  'icons' =>
                                    array (
                                      0 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'facebook',
                                          'link' => 'http://www.facebook.com',
                                          'image' => $this->social_icon_url . '/02-grey/Facebook.png?mailpoet_version=3.0.0-rc.1.0.4',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Facebook',
                                        ),
                                      1 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'twitter',
                                          'link' => 'http://www.twitter.com',
                                          'image' => $this->social_icon_url . '/02-grey/Twitter.png?mailpoet_version=3.0.0-rc.1.0.4',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Twitter',
                                        ),
                                      2 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'youtube',
                                          'link' => 'http://www.youtube.com',
                                          'image' => $this->social_icon_url . '/02-grey/Youtube.png?mailpoet_version=3.0.0-rc.1.0.4',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Youtube',
                                        ),
                                      3 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'email',
                                          'link' => '',
                                          'image' => $this->social_icon_url . '/02-grey/Email.png?mailpoet_version=3.0.0-rc.1.0.4',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Email',
                                        ),
                                    ),
                                ),
                              3 =>
                                array (
                                  'type' => 'footer',
                                  'text' => '<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage your subscription</a><br />Add your postal address here!</p>',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => 'transparent',
                                        ),
                                      'text' =>
                                        array (
                                          'fontColor' => '#222222',
                                          'fontFamily' => 'Courier New',
                                          'fontSize' => '12px',
                                          'textAlign' => 'center',
                                        ),
                                      'link' =>
                                        array (
                                          'fontColor' => '#008282',
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
      'globalStyles' =>
        array (
          'text' =>
            array (
              'fontColor' => '#000000',
              'fontFamily' => 'Courier New',
              'fontSize' => '14px',
            ),
          'h1' =>
            array (
              'fontColor' => '#111111',
              'fontFamily' => 'Courier New',
              'fontSize' => '30px',
            ),
          'h2' =>
            array (
              'fontColor' => '#222222',
              'fontFamily' => 'Courier New',
              'fontSize' => '24px',
            ),
          'h3' =>
            array (
              'fontColor' => '#333333',
              'fontFamily' => 'Courier New',
              'fontSize' => '18px',
            ),
          'link' =>
            array (
              'fontColor' => '#008282',
              'textDecoration' => 'underline',
            ),
          'wrapper' =>
            array (
              'backgroundColor' => '#ffffff',
            ),
          'body' =>
            array (
              'backgroundColor' => '#008282',
            ),
        ),
    );
  }


}