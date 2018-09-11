<?php

namespace MailPoet\Config\PopulatorData\Templates;

class WideStoryLayout {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/wide-story-layout';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Wide Story Layout", 'mailpoet'),
      'categories' => json_encode(array('notification', 'sample')),
      'readonly' => 1,
      'thumbnail' => $this->getThumbnail(),
      'body' => json_encode($this->getBody()),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/wide-story-layout.jpg';
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
                          'backgroundColor' => '#f0f0f0',
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
                                          'height' => '50px',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Wide-Logo.png',
                                  'alt' => 'Wide-Logo',
                                  'fullWidth' => false,
                                  'width' => '200px',
                                  'height' => '37px',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                              2 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<h3 style="text-align: center;"><span style="color: #808080;">Our Latest Posts</span></h3>',
                                ),
                              3 =>
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
                                  'type' => 'image',
                                  'link' => 'http://mailpoet.info/cutting-through-the-smog-what-to-do-to-fight-air-pollution/',
                                  'src' => $this->template_image_url . '/5_what_to_do_p352m1141746-800x533.jpg',
                                  'alt' => 'Cutting through the smog: What to do to fight air pollution',
                                  'fullWidth' => true,
                                  'width' => 660,
                                  'height' => 440,
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
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
                                  'text' => '<h3 style="text-align: center;" data-post-id="1997">Cutting through the smog: What to do to fight air pollution</h3>
<p class="mailpoet_wp_post" style="text-align: center;">Tackling our air problems starts with traffic control, but individual action to reduce energy use and intensive farming would also help clean our air.</p>',
                                ),
                              3 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'Read The Post',
                                  'url' => 'http://mailpoet.info/cutting-through-the-smog-what-to-do-to-fight-air-pollution/',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#5ecd39',
                                          'borderColor' => '#000000',
                                          'borderWidth' => '0px',
                                          'borderRadius' => '5px',
                                          'borderStyle' => 'solid',
                                          'width' => '288px',
                                          'lineHeight' => '36px',
                                          'fontColor' => '#ffffff',
                                          'fontFamily' => 'Lucida',
                                          'fontSize' => '16px',
                                          'fontWeight' => 'normal',
                                          'textAlign' => 'center',
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
                                          'backgroundColor' => '#f0f0f0',
                                          'height' => '40px',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'image',
                                  'link' => 'http://mailpoet.info/cutting-through-the-smog-what-to-do-to-fight-air-pollution/',
                                  'src' => $this->template_image_url . '/gettyimages-578313682-800x533.jpg',
                                  'alt' => 'gettyimages-578313682-800x533',
                                  'fullWidth' => true,
                                  'width' => '800px',
                                  'height' => '533px',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                              2 =>
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
                              3 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<h3 style="text-align: center;" data-post-id="1997">Ladybird&rsquo;s transparent shell reveals how it folds its wings</h3>
<p class="mailpoet_wp_post" style="text-align: center;">They certainly know how to fold. A see-through artificial wing case has been used to watch for the first time as ladybirds put away their wings after flight.</p>',
                                ),
                              4 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'Read The Post',
                                  'url' => '',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#5ecd39',
                                          'borderColor' => '#000000',
                                          'borderWidth' => '0px',
                                          'borderRadius' => '5px',
                                          'borderStyle' => 'solid',
                                          'width' => '288px',
                                          'lineHeight' => '36px',
                                          'fontColor' => '#ffffff',
                                          'fontFamily' => 'Lucida',
                                          'fontSize' => '16px',
                                          'fontWeight' => 'normal',
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                              5 =>
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
                              6 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#f0f0f0',
                                          'height' => '40px',
                                        ),
                                    ),
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
                                  'type' => 'image',
                                  'link' => 'http://mailpoet.info/cutting-through-the-smog-what-to-do-to-fight-air-pollution/',
                                  'src' => $this->template_image_url . '/plasma-stingray111-800x533.jpg',
                                  'alt' => 'plasma-stingray111-800x533',
                                  'fullWidth' => true,
                                  'width' => '800px',
                                  'height' => '533px',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
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
                                  'text' => '<h3 style="text-align: center;" data-post-id="1997">Plasma jet engines that could take you from the ground to space</h3>
<p class="mailpoet_wp_post" style="text-align: center;">Forget fuel-powered jet engines. We&rsquo;re on the verge of having aircraft that can fly from the ground up to the edge of space using air and electricity alone.</p>',
                                ),
                              3 =>
                                array (
                                  'type' => 'button',
                                  'text' => 'Read The Post',
                                  'url' => '',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#5ecd39',
                                          'borderColor' => '#000000',
                                          'borderWidth' => '0px',
                                          'borderRadius' => '5px',
                                          'borderStyle' => 'solid',
                                          'width' => '288px',
                                          'lineHeight' => '36px',
                                          'fontColor' => '#ffffff',
                                          'fontFamily' => 'Lucida',
                                          'fontSize' => '16px',
                                          'fontWeight' => 'normal',
                                          'textAlign' => 'center',
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
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#f0f0f0',
                                          'height' => '40px',
                                        ),
                                    ),
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
                                  'type' => 'image',
                                  'link' => '',
                                  'src' => $this->template_image_url . '/Wide-Footer.jpg',
                                  'alt' => 'Wide-Footer',
                                  'fullWidth' => true,
                                  'width' => '1280px',
                                  'height' => '721px',
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
              5 =>
                array (
                  'type' => 'container',
                  'orientation' => 'horizontal',
                  'styles' =>
                    array (
                      'block' =>
                        array (
                          'backgroundColor' => '#5ecd39',
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
                                          'height' => '21px',
                                        ),
                                    ),
                                ),
                              1 =>
                                array (
                                  'type' => 'social',
                                  'iconSet' => 'full-symbol-grey',
                                  'icons' =>
                                    array (
                                      0 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'facebook',
                                          'link' => 'http://www.facebook.com',
                                          'image' => $this->social_icon_url . '/08-full-symbol-grey/Facebook.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Facebook',
                                        ),
                                      1 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'twitter',
                                          'link' => 'http://www.twitter.com',
                                          'image' => $this->social_icon_url . '/08-full-symbol-grey/Twitter.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Twitter',
                                        ),
                                      2 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'website',
                                          'link' => '',
                                          'image' => $this->social_icon_url . '/08-full-symbol-grey/Website.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Website',
                                        ),
                                      3 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'instagram',
                                          'link' => 'http://instagram.com',
                                          'image' => $this->social_icon_url . '/08-full-symbol-grey/Instagram.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Instagram',
                                        ),
                                    ),
                                ),
                              2 =>
                                array (
                                  'type' => 'footer',
                                  'text' => '<p><span style="color: #ffffff;"><a href="[link:subscription_unsubscribe_url]" style="color: #ffffff;">Unsubscribe</a> | <a href="[link:subscription_manage_url]" style="color: #ffffff;">Manage subscription</a></span><br /><span style="color: #ffffff;">Add your postal address here!</span></p>',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => 'transparent',
                                        ),
                                      'text' =>
                                        array (
                                          'fontColor' => '#222222',
                                          'fontFamily' => 'Arial',
                                          'fontSize' => '12px',
                                          'textAlign' => 'center',
                                        ),
                                      'link' =>
                                        array (
                                          'fontColor' => '#6cb7d4',
                                          'textDecoration' => 'none',
                                        ),
                                    ),
                                ),
                              3 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
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
      'globalStyles' =>
        array (
          'text' =>
            array (
              'fontColor' => '#000000',
              'fontFamily' => 'Arial',
              'fontSize' => '15px',
            ),
          'h1' =>
            array (
              'fontColor' => '#111111',
              'fontFamily' => 'Lucida',
              'fontSize' => '30px',
            ),
          'h2' =>
            array (
              'fontColor' => '#222222',
              'fontFamily' => 'Lucida',
              'fontSize' => '24px',
            ),
          'h3' =>
            array (
              'fontColor' => '#333333',
              'fontFamily' => 'Lucida',
              'fontSize' => '18px',
            ),
          'link' =>
            array (
              'fontColor' => '#5ecd39',
              'textDecoration' => 'underline',
            ),
          'wrapper' =>
            array (
              'backgroundColor' => '#ffffff',
            ),
          'body' =>
            array (
              'backgroundColor' => '#f0f0f0',
            ),
        ),
    );
  }

}