<?php

namespace MailPoet\Config\PopulatorData\Templates;
use MailPoet\WP\Functions as WPFunctions;

class CityLocalNews {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/city-local-news';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => WPFunctions::get()->__("City News", 'mailpoet'),
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
                'backgroundColor' => '#252525',
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
                    'text' => '<p><span style="color: #999999;"><a href="[link:newsletter_view_in_browser_url]" style="color: #999999;">View online &gt;</a></span></p>',
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
                        'textAlign' => 'right',
                      ),
                      'link' => 
                      array (
                        'fontColor' => '#6cb7d4',
                        'textDecoration' => 'underline',
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
              'src' => $this->template_image_url . '/CityNews-Header.jpg',
              'display' => 'scale',
            ),
            'styles' => 
            array (
              'block' => 
              array (
                'backgroundColor' => '#000000',
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
                        'height' => '70px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/CityNews-Logo.png',
                    'alt' => 'CityNews-Logo',
                    'fullWidth' => false,
                    'width' => '150px',
                    'height' => '178px',
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
                    'text' => '<h1 style="text-align: center;"><span style="color: #ffffff;"><strong>[date:mtext] [date:y]</strong></span></h1>
    <p style="text-align: center;"><span style="color: #ffffff;">N E W S L E T T E R</span></p>',
                  ),
                  3 => 
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
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Facebook.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                      1 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Twitter.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                      2 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'email',
                        'link' => '',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Email.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Email',
                      ),
                      3 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'youtube',
                        'link' => 'http://www.youtube.com',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Youtube.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Youtube',
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
                'backgroundColor' => '#252525',
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
                        'height' => '22px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'text',
                    'text' => '<p style="text-align: left;"><span style="color: #333333;"><strong><span></span></strong></span><span style="color: #999999;"><strong>TOP STORY</strong></span></p>
    <h2 style="text-align: left;"><span style="color: #333333;"><strong>Park opens to all within the community</strong></span></h2>
    <p><span style="color: #333333;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus ac maximus justo. Phasellus ullamcorper, enim at eleifend malesuada, risus purus interdum arcu, sed porttitor erat diam a neque.&nbsp;</span></p>
    <p><span style="color: #333333;"></span></p>
    <p><strong><a href="http://mailpoet-sg.com">Read full article &gt;</a></strong></p>',
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
                    'src' => $this->template_image_url . '/CityNews-Images-1.jpg',
                    'alt' => 'CityNews-Images-1',
                    'fullWidth' => true,
                    'width' => '600px',
                    'height' => '600px',
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
                'backgroundColor' => '#252525',
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
          5 => 
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
                  1 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/CityNews-ThinImages-1.jpg',
                    'alt' => 'CityNews-ThinImages-1',
                    'fullWidth' => false,
                    'width' => '600px',
                    'height' => '300px',
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
                    'text' => '<p style="text-align: left;"><span style="color: #999999;"><strong>EVENTS</strong></span></p>
    <h3 style="text-align: left;"><span style="color: #333333;"><strong>Christmas Market opening soon</strong></span></h3>
    <p><strong><a href="http://mailpoet-sg.com">Read full article &gt;</a></strong></p>',
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
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/CityNews-ThinImages-2.jpg',
                    'alt' => 'CityNews-ThinImages-2',
                    'fullWidth' => false,
                    'width' => '600px',
                    'height' => '300px',
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
                    'text' => '<p style="text-align: left;"><span style="color: #999999;"><strong>HEALTHCARE</strong></span></p>
    <h3 style="text-align: left;"><span style="color: #333333;"><strong>Hospitals brace for cuts to services</strong></span></h3>
    <p><strong><a href="http://mailpoet-sg.com">Read full article &gt;</a></strong></p>',
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
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/CityNews-ThinImages-3.jpg',
                    'alt' => 'CityNews-ThinImages-3',
                    'fullWidth' => false,
                    'width' => '600px',
                    'height' => '300px',
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
                    'text' => '<p style="text-align: left;"><span style="color: #999999;"><strong>WEATHER</strong></span></p>
    <h3 style="text-align: left;"><span style="color: #333333;"><strong>Dry heat has a high chance to cause fires</strong></span></h3>
    <p><strong><a href="http://mailpoet-sg.com">Read full article &gt;</a></strong></p>',
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
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/CityNews-ThinImages-4.jpg',
                    'alt' => 'CityNews-ThinImages-4',
                    'fullWidth' => false,
                    'width' => '600px',
                    'height' => '300px',
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
                    'text' => '<p style="text-align: left;"><span style="color: #999999;"><strong>ARTS</strong></span></p>
    <h3 style="text-align: left;"><span style="color: #333333;"><strong>Local theatre to stage dramatic new play</strong></span></h3>
    <p><strong><a href="http://mailpoet-sg.com">Read full article &gt;</a></strong></p>',
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
                'backgroundColor' => '#252525',
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
                'backgroundColor' => '#252525',
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
                    'src' => $this->template_image_url . '/CityNews-Phone.png',
                    'alt' => 'CityNews-Phone',
                    'fullWidth' => false,
                    'width' => '162px',
                    'height' => '679px',
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
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'text',
                    'text' => '<h3 style="text-align: left;"><span style="color: #ffffff;"><strong>Download the CityNews app</strong></span></h3>
    <p><span style="color: #d1d1d1;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus ac maximus justo. Phasellus ullamcorper, enim at eleifend malesuada, risus purus interdum arcu, sed porttitor erat diam a neque.&nbsp;</span></p>',
                  ),
                  2 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/CityNews-GoogleDownload.png',
                    'alt' => 'CityNews-GoogleDownload',
                    'fullWidth' => false,
                    'width' => '150px',
                    'height' => '168px',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'textAlign' => 'left',
                      ),
                    ),
                  ),
                  3 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/CityNews-AppleDownload.png',
                    'alt' => 'CityNews-AppleDownload',
                    'fullWidth' => false,
                    'width' => '150px',
                    'height' => '168px',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'textAlign' => 'left',
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
              'src' => NULL,
              'display' => 'scale',
            ),
            'styles' => 
            array (
              'block' => 
              array (
                'backgroundColor' => '#252525',
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
                    'type' => 'divider',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => 'transparent',
                        'padding' => '13px',
                        'borderStyle' => 'solid',
                        'borderWidth' => '1px',
                        'borderColor' => '#4b4b4b',
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
                        'height' => '20px',
                      ),
                    ),
                  ),
                  2 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/CityNews-Logo.png',
                    'alt' => 'CityNews-Logo',
                    'fullWidth' => false,
                    'width' => '122px',
                    'height' => '178px',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  3 => 
                  array (
                    'type' => 'footer',
                    'text' => '<p><span style="color: #999999;"><a href="[link:subscription_unsubscribe_url]" style="color: #999999;">Unsubscribe</a> | <a href="[link:subscription_manage_url]" style="color: #999999;">Manage subscription</a></span><br /><span style="color: #ffffff;">Add your postal address here!</span></p>',
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
                  4 => 
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
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Facebook.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                      1 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Twitter.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                      2 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'email',
                        'link' => '',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Email.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Email',
                      ),
                      3 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'youtube',
                        'link' => 'http://www.youtube.com',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Youtube.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Youtube',
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
          'fontFamily' => 'Source Sans Pro',
          'fontSize' => '16px',
        ),
        'h1' => 
        array (
          'fontColor' => '#111111',
          'fontFamily' => 'Merriweather Sans',
          'fontSize' => '26px',
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
          'fontSize' => '20px',
        ),
        'link' => 
        array (
          'fontColor' => '#252525',
          'textDecoration' => 'underline',
        ),
        'wrapper' => 
        array (
          'backgroundColor' => '#f8f8f8',
        ),
        'body' => 
        array (
          'backgroundColor' => '#252525',
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
          'text' => 'Read more',
          'url' => '[postLink]',
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
          'type' => 'button',
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
              'borderWidth' => '1px',
              'borderColor' => '#4b4b4b',
            ),
          ),
          'type' => 'divider',
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
          'featuredImagePosition' => 'left',
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
            'type' => 'button',
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
                'borderWidth' => '1px',
                'borderColor' => '#4b4b4b',
              ),
            ),
            'type' => 'divider',
          ),
          'backgroundColor' => '#ffffff',
          'backgroundColorAlternate' => '#eeeeee',
          'type' => 'posts',
          'offset' => 0,
          'terms' => 
          array (
          ),
          'search' => '',
        ),
        'social' => 
        array (
          'iconSet' => 'full-symbol-grey',
          'icons' => 
          array (
            0 => 
            array (
              'type' => 'socialIcon',
              'iconType' => 'facebook',
              'link' => 'http://www.facebook.com',
              'image' => $this->social_icon_url . '/08-full-symbol-grey/Facebook.png',
              'height' => '32px',
              'width' => '32px',
              'text' => 'Facebook',
            ),
            1 => 
            array (
              'type' => 'socialIcon',
              'iconType' => 'twitter',
              'link' => 'http://www.twitter.com',
              'image' => $this->social_icon_url . '/08-full-symbol-grey/Twitter.png',
              'height' => '32px',
              'width' => '32px',
              'text' => 'Twitter',
            ),
            2 => 
            array (
              'type' => 'socialIcon',
              'iconType' => 'email',
              'link' => '',
              'image' => $this->social_icon_url . '/08-full-symbol-grey/Email.png',
              'height' => '32px',
              'width' => '32px',
              'text' => 'Email',
            ),
            3 => 
            array (
              'type' => 'socialIcon',
              'iconType' => 'youtube',
              'link' => 'http://www.youtube.com',
              'image' => $this->social_icon_url . '/08-full-symbol-grey/Youtube.png',
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
              'height' => '40px',
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
              'fontFamily' => 'Arial',
              'fontSize' => '12px',
              'textAlign' => 'right',
            ),
            'link' => 
            array (
              'fontColor' => '#6cb7d4',
              'textDecoration' => 'underline',
            ),
          ),
          'type' => 'header',
        ),
      ),
    );
  }

}
