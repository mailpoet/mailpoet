<?php

namespace MailPoet\Config\PopulatorData\Templates;

class Painter {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/painter';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Painter", 'mailpoet'),
      'categories' => json_encode(array('standard', 'all')),
      'readonly' => 1,
      'thumbnail' => $this->getThumbnail(),
      'body' => json_encode($this->getBody()),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/thumbnail.jpg';
  }

  private function getBody() {
    return array (
      'content' =>
      array (
        'type' => 'container',
        'columnLayout' => false,
        'orientation' => 'vertical',
        'image' =>
        array (
          'src' => NULL,
          'display' => 'scale',
        ),
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
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' =>
            array (
              'src' => NULL,
              'display' => 'scale',
            ),
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
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' =>
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
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
                        'height' => '30px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'header',
                    'text' => '<p><a href="[link:newsletter_view_in_browser_url]">View in browser &gt;</a></p>',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => 'transparent',
                      ),
                      'text' =>
                      array (
                        'fontColor' => '#222222',
                        'fontFamily' => 'Merriweather',
                        'fontSize' => '11px',
                        'textAlign' => 'center',
                      ),
                      'link' =>
                      array (
                        'fontColor' => '#787878',
                        'textDecoration' => 'none',
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
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' =>
            array (
              'src' => NULL,
              'display' => 'scale',
            ),
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
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' =>
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
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
                ),
              ),
            ),
          ),
          2 =>
          array (
            'type' => 'container',
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' =>
            array (
              'src' => $this->template_image_url . '/Painter-Logo-bg.jpg',
              'display' => 'scale',
            ),
            'styles' =>
            array (
              'block' =>
              array (
                'backgroundColor' => '#8289ca',
              ),
            ),
            'blocks' =>
            array (
              0 =>
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' =>
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
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
                        'height' => '100px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Painter-Logo.png',
                    'alt' => 'Painter-Logo',
                    'fullWidth' => false,
                    'width' => '305.9375px',
                    'height' => '93px',
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
                        'height' => '100px',
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
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' =>
            array (
              'src' => NULL,
              'display' => 'scale',
            ),
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
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' =>
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
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
                        'height' => '30px',
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
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' =>
            array (
              'src' => NULL,
              'display' => 'scale',
            ),
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
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' =>
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
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
                    'text' => '<p style="text-align: center;"><a href="https://www.mailpoet.com">Reviews &amp; Submissions</a></p>',
                  ),
                ),
              ),
              1 =>
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' =>
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
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
                    'text' => '<p style="text-align: center;"><a href="https://www.mailpoet.com">Essential&nbsp;Gear</a></p>',
                  ),
                ),
              ),
              2 =>
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' =>
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
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
                    'text' => '<p style="text-align: center;"><a href="https://www.mailpoet.com">Latest Commissions</a></p>',
                  ),
                ),
              ),
            ),
          ),
          5 =>
          array (
            'type' => 'container',
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' =>
            array (
              'src' => $this->template_image_url . '/Painter-Header.jpg',
              'display' => 'scale',
            ),
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
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' =>
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
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
                        'height' => '100px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<h1><strong>Welcome back</strong></h1>
    <p>It\'s been a big month for the studio and we\'d love to show you some of our recent work.</p>',
                  ),
                  2 =>
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
                  3 =>
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/186018-sktchy-2-nh-gutenCRALTBRBLOG.jpg',
                    'alt' => '186018-sktchy-2-nh-gutenCRALTBRBLOG',
                    'fullWidth' => false,
                    'width' => '520px',
                    'height' => '830px',
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
              1 =>
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' =>
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
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
                        'height' => '140px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/181215_2-laura-anderson-dog-fab-artistico-cpCRAltBRCRBlog.jpg',
                    'alt' => '181215_2-laura-anderson-dog-fab-artistico-cpCRAltBRCRBlog',
                    'fullWidth' => false,
                    'width' => '180px',
                    'height' => '1010px',
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
                        'height' => '40px',
                      ),
                    ),
                  ),
                  3 =>
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/170204_sktchy_BlackChickenBrushPen2CRBR.jpg',
                    'alt' => '170204_sktchy_BlackChickenBrushPen2CRBR',
                    'fullWidth' => false,
                    'width' => '328px',
                    'height' => '686px',
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
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' =>
            array (
              'src' => NULL,
              'display' => 'scale',
            ),
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
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' =>
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
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
                        'height' => '40px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<h2 style="text-align: center;"><strong>Latest from the blog</strong></h2>',
                  ),
                ),
              ),
            ),
          ),
          7 =>
          array (
            'type' => 'container',
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' =>
            array (
              'src' => NULL,
              'display' => 'scale',
            ),
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
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' =>
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
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
                    'src' => $this->template_image_url . '/1812231-sktchy-1-jon-coxwell-fabriano-cpCRFeat-394x252.jpg',
                    'alt' => '1812231-sktchy-1-jon-coxwell-fabriano-cpCRFeat-394x252',
                    'fullWidth' => false,
                    'width' => '394px',
                    'height' => '252px',
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
                    'type' => 'text',
                    'text' => '<p><span>Self-Assessment: 2018&mdash;Filling Journals, Using Up Paint</span></p>
    <p><a href="https://www.mailpoet.com">Read more &gt;</a></p>',
                  ),
                ),
              ),
              1 =>
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' =>
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
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
                    'src' => $this->template_image_url . '/180830-2018-fair-32-pigeon-fabri-300-hpCRAltBRFeat-394x252.jpg',
                    'alt' => '180830-2018-fair-32-pigeon-fabri-300-hpCRAltBRFeat-394x252',
                    'fullWidth' => false,
                    'width' => '394px',
                    'height' => '252px',
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
                    'type' => 'text',
                    'text' => '<p><span>Happy New Year 2019</span></p>
    <p><a href="https://www.mailpoet.com">Read more &gt;</a></p>',
                  ),
                ),
              ),
              2 =>
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' =>
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
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
                    'src' => $this->template_image_url . '/180828-sktchy-t-c-c-hahn-expressions-cpCRAltBRCRFeat-394x252.jpg',
                    'alt' => '180828-sktchy-t-c-c-hahn-expressions-cpCRAltBRCRFeat-394x252',
                    'fullWidth' => false,
                    'width' => '394px',
                    'height' => '252px',
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
                    'type' => 'text',
                    'text' => '<p><span>Roz&rsquo;s 2018 Minnesota State Fair Journal</span></p>
    <p><a href="https://www.mailpoet.com">Read more &gt;</a></p>',
                  ),
                ),
              ),
            ),
          ),
          8 =>
          array (
            'type' => 'container',
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' =>
            array (
              'src' => NULL,
              'display' => 'scale',
            ),
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
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' =>
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
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
                ),
              ),
            ),
          ),
          9 =>
          array (
            'type' => 'container',
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' =>
            array (
              'src' => $this->template_image_url . '/Painter-SocialBack.jpg',
              'display' => 'scale',
            ),
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
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' =>
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
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
                        'height' => '64px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<h3 style="text-align: center;"><strong>Stay in touch</strong></h3>
    <p style="text-align: center;"><span>Keep up-to-date with all of the work from the studio</span></p>',
                  ),
                  2 =>
                  array (
                    'type' => 'social',
                    'iconSet' => 'full-symbol-black',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'textAlign' => 'center',
                      ),
                    ),
                    'icons' =>
                    array (
                      0 =>
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://www.facebook.com',
                        'image' => $this->social_icon_url . '/07-full-symbol-black/Facebook.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                      1 =>
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com',
                        'image' => $this->social_icon_url . '/07-full-symbol-black/Twitter.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                      2 =>
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'instagram',
                        'link' => 'http://instagram.com',
                        'image' => $this->social_icon_url . '/07-full-symbol-black/Instagram.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Instagram',
                      ),
                      3 =>
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'youtube',
                        'link' => 'http://www.youtube.com',
                        'image' => $this->social_icon_url . '/07-full-symbol-black/Youtube.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Youtube',
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
          10 =>
          array (
            'type' => 'container',
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' =>
            array (
              'src' => NULL,
              'display' => 'scale',
            ),
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
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' =>
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
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
                        'height' => '40px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<p style="text-align: center;"><strong>Canvas Studio</strong></p>
    <p style="text-align: center; font-size: 11px;"><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a>&nbsp;|&nbsp;<a href="[link:subscription_manage_url]">Manage subscription</a><br />Add your postal address here!</p>',
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
          'fontFamily' => 'Merriweather',
          'fontSize' => '14px',
        ),
        'h1' =>
        array (
          'fontColor' => '#111111',
          'fontFamily' => 'Merriweather',
          'fontSize' => '36px',
        ),
        'h2' =>
        array (
          'fontColor' => '#222222',
          'fontFamily' => 'Merriweather',
          'fontSize' => '24px',
        ),
        'h3' =>
        array (
          'fontColor' => '#333333',
          'fontFamily' => 'Merriweather',
          'fontSize' => '22px',
        ),
        'link' =>
        array (
          'fontColor' => '#585858',
          'textDecoration' => 'underline',
        ),
        'wrapper' =>
        array (
          'backgroundColor' => '#ffffff',
        ),
        'body' =>
        array (
          'backgroundColor' => '#ffffff',
        ),
      ),
      'blockDefaults' =>
      array (
        'automatedLatestContent' =>
        array (
          'amount' => '5',
          'withLayout' => false,
          'contentType' => 'post',
          'inclusionType' => 'include',
          'displayType' => 'excerpt',
          'titleFormat' => 'h1',
          'titleAlignment' => 'left',
          'titleIsLink' => false,
          'imageFullWidth' => false,
          'featuredImagePosition' => 'belowTitle',
          'showAuthor' => 'no',
          'authorPrecededBy' => 'Author:',
          'showCategories' => 'no',
          'categoriesPrecededBy' => 'Categories:',
          'readMoreType' => 'button',
          'readMoreText' => 'Read more',
          'readMoreButton' =>
          array (
            'text' => 'Read more',
            'url' => '[postLink]',
            'context' => 'automatedLatestContent.readMoreButton',
            'styles' =>
            array (
              'block' =>
              array (
                'backgroundColor' => '#2ea1cd',
                'borderColor' => '#0074a2',
                'borderWidth' => '1px',
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
          'sortBy' => 'newest',
          'showDivider' => true,
          'divider' =>
          array (
            'context' => 'automatedLatestContent.divider',
            'styles' =>
            array (
              'block' =>
              array (
                'backgroundColor' => 'transparent',
                'padding' => '13px',
                'borderStyle' => 'solid',
                'borderWidth' => '3px',
                'borderColor' => '#aaaaaa',
              ),
            ),
          ),
          'backgroundColor' => '#ffffff',
          'backgroundColorAlternate' => '#eeeeee',
        ),
        'automatedLatestContentLayout' =>
        array (
          'amount' => '5',
          'withLayout' => true,
          'contentType' => 'post',
          'inclusionType' => 'include',
          'displayType' => 'excerpt',
          'titleFormat' => 'h1',
          'titleAlignment' => 'left',
          'titleIsLink' => false,
          'imageFullWidth' => false,
          'featuredImagePosition' => 'alternate',
          'showAuthor' => 'no',
          'authorPrecededBy' => 'Author:',
          'showCategories' => 'no',
          'categoriesPrecededBy' => 'Categories:',
          'readMoreType' => 'button',
          'readMoreText' => 'Read more',
          'readMoreButton' =>
          array (
            'text' => 'Read more',
            'url' => '[postLink]',
            'context' => 'automatedLatestContentLayout.readMoreButton',
            'styles' =>
            array (
              'block' =>
              array (
                'backgroundColor' => '#2ea1cd',
                'borderColor' => '#0074a2',
                'borderWidth' => '1px',
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
          'sortBy' => 'newest',
          'showDivider' => true,
          'divider' =>
          array (
            'context' => 'automatedLatestContentLayout.divider',
            'styles' =>
            array (
              'block' =>
              array (
                'backgroundColor' => 'transparent',
                'padding' => '13px',
                'borderStyle' => 'solid',
                'borderWidth' => '3px',
                'borderColor' => '#aaaaaa',
              ),
            ),
          ),
          'backgroundColor' => '#ffffff',
          'backgroundColorAlternate' => '#eeeeee',
        ),
        'button' =>
        array (
          'text' => 'Button',
          'url' => '',
          'styles' =>
          array (
            'block' =>
            array (
              'backgroundColor' => '#2ea1cd',
              'borderColor' => '#0074a2',
              'borderWidth' => '1px',
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
        'divider' =>
        array (
          'styles' =>
          array (
            'block' =>
            array (
              'backgroundColor' => 'transparent',
              'padding' => '13px',
              'borderStyle' => 'solid',
              'borderWidth' => '3px',
              'borderColor' => '#aaaaaa',
            ),
          ),
        ),
        'footer' =>
        array (
          'text' => '<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage subscription</a><br />Add your postal address here!</p>',
          'styles' =>
          array (
            'block' =>
            array (
              'backgroundColor' => 'transparent',
            ),
            'text' =>
            array (
              'fontColor' => '#222222',
              'fontFamily' => 'Merriweather',
              'fontSize' => '12px',
              'textAlign' => 'center',
            ),
            'link' =>
            array (
              'fontColor' => '#585858',
              'textDecoration' => 'none',
            ),
          ),
          'type' => 'footer',
        ),
        'posts' =>
        array (
          'amount' => '10',
          'withLayout' => true,
          'contentType' => 'post',
          'postStatus' => 'publish',
          'inclusionType' => 'include',
          'displayType' => 'excerpt',
          'titleFormat' => 'h1',
          'titleAlignment' => 'left',
          'titleIsLink' => false,
          'imageFullWidth' => false,
          'featuredImagePosition' => 'alternate',
          'showAuthor' => 'no',
          'authorPrecededBy' => 'Author:',
          'showCategories' => 'no',
          'categoriesPrecededBy' => 'Categories:',
          'readMoreType' => 'link',
          'readMoreText' => 'Read more',
          'readMoreButton' =>
          array (
            'text' => 'Read more',
            'url' => '[postLink]',
            'context' => 'posts.readMoreButton',
            'styles' =>
            array (
              'block' =>
              array (
                'backgroundColor' => '#2ea1cd',
                'borderColor' => '#0074a2',
                'borderWidth' => '1px',
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
          'sortBy' => 'newest',
          'showDivider' => true,
          'divider' =>
          array (
            'context' => 'posts.divider',
            'styles' =>
            array (
              'block' =>
              array (
                'backgroundColor' => 'transparent',
                'padding' => '13px',
                'borderStyle' => 'solid',
                'borderWidth' => '3px',
                'borderColor' => '#aaaaaa',
              ),
            ),
          ),
          'backgroundColor' => '#ffffff',
          'backgroundColorAlternate' => '#eeeeee',
        ),
        'social' =>
        array (
          'iconSet' => 'full-symbol-black',
          'icons' =>
          array (
            0 =>
            array (
              'type' => 'socialIcon',
              'iconType' => 'facebook',
              'link' => 'http://www.facebook.com',
              'image' => $this->social_icon_url . '/07-full-symbol-black/Facebook.png',
              'height' => '32px',
              'width' => '32px',
              'text' => 'Facebook',
            ),
            1 =>
            array (
              'type' => 'socialIcon',
              'iconType' => 'twitter',
              'link' => 'http://www.twitter.com',
              'image' => $this->social_icon_url . '/07-full-symbol-black/Twitter.png',
              'height' => '32px',
              'width' => '32px',
              'text' => 'Twitter',
            ),
            2 =>
            array (
              'type' => 'socialIcon',
              'iconType' => 'instagram',
              'link' => 'http://instagram.com',
              'image' => $this->social_icon_url . '/07-full-symbol-black/Instagram.png',
              'height' => '32px',
              'width' => '32px',
              'text' => 'Instagram',
            ),
            3 =>
            array (
              'type' => 'socialIcon',
              'iconType' => 'youtube',
              'link' => 'http://www.youtube.com',
              'image' => $this->social_icon_url . '/07-full-symbol-black/Youtube.png',
              'height' => '32px',
              'width' => '32px',
              'text' => 'Youtube',
            ),
          ),
          'type' => 'social',
        ),
        'spacer' =>
        array (
          'styles' =>
          array (
            'block' =>
            array (
              'backgroundColor' => 'transparent',
              'height' => '100px',
            ),
          ),
          'type' => 'spacer',
        ),
        'header' =>
        array (
          'text' => 'Display problems?&nbsp;<a href="[link:newsletter_view_in_browser_url]">Open this email in your web browser.</a>',
          'styles' =>
          array (
            'block' =>
            array (
              'backgroundColor' => 'transparent',
            ),
            'text' =>
            array (
              'fontColor' => '#222222',
              'fontFamily' => 'Merriweather',
              'fontSize' => '11px',
              'textAlign' => 'center',
            ),
            'link' =>
            array (
              'fontColor' => '#787878',
              'textDecoration' => 'none',
            ),
          ),
          'type' => 'header',
        ),
      ),
    );
  }

}
