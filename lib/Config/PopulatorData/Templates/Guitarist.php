<?php

namespace MailPoet\Config\PopulatorData\Templates;

class Guitarist {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/guitarist';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Guitarist", 'mailpoet'),
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
                'backgroundColor' => '#27282e',
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
                        'height' => '23px',
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
                'backgroundColor' => '#27282e',
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
                    'src' => $this->template_image_url . '/Guitarist-Logo.png',
                    'alt' => 'Guitarist-Logo',
                    'fullWidth' => false,
                    'width' => '184px',
                    'height' => '179px',
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
                        'height' => '26px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'social',
                    'iconSet' => 'full-symbol-grey',
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
                    'type' => 'spacer',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => 'transparent',
                        'height' => '25px',
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
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '12px',
                        'textAlign' => 'center',
                      ),
                      'link' =>
                      array (
                        'fontColor' => '#949494',
                        'textDecoration' => 'underline',
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
                'backgroundColor' => '#27282e',
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
                        'height' => '23px',
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
              'src' => $this->template_image_url . '/Guitarist-Header.jpg',
              'display' => 'scale',
            ),
            'styles' =>
            array (
              'block' =>
              array (
                'backgroundColor' => '#27282e',
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
                        'height' => '700px',
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
                        'height' => '250px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<h3><strong>Exit Stage Right</strong></h3>
    <p><span style="color: #999999;">A brand new album releasing this summer on <span style="color: #ffffff;">iTunes</span> and <span style="color: #1db954;">Spotify</span></span></p>',
                  ),
                  2 =>
                  array (
                    'type' => 'button',
                    'text' => 'Find out more',
                    'url' => '',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#4c537e',
                        'borderColor' => '#0074a2',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '95px',
                        'lineHeight' => '30px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '12px',
                        'fontWeight' => 'normal',
                        'textAlign' => 'left',
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
                    'type' => 'spacer',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => 'transparent',
                        'height' => '130px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<h3 style="text-align: center;"><span style="color: #27282e;"><strong>My recording setup</strong></span></h3>
    <p style="text-align: center;"><span style="color: #5a5c66;">A&nbsp;lot of people have been asking how I record my albums, so I thought I would write a quick blog post to explain</span></p>',
                  ),
                  2 =>
                  array (
                    'type' => 'button',
                    'text' => 'Find out more',
                    'url' => '',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#4c537e',
                        'borderColor' => '#0074a2',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '95px',
                        'lineHeight' => '30px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '12px',
                        'fontWeight' => 'normal',
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Guitarist-Images-1-1.jpg',
                    'alt' => 'Guitarist-Images-1',
                    'fullWidth' => true,
                    'width' => '800px',
                    'height' => '1000px',
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
                    'src' => $this->template_image_url . '/Guitarist-Images-2-1.jpg',
                    'alt' => 'Guitarist-Images-2',
                    'fullWidth' => true,
                    'width' => '800px',
                    'height' => '1000px',
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
                        'height' => '130px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<h3 style="text-align: center;"><span style="color: #27282e;"><strong>Life on the road</strong></span></h3>
    <p style="text-align: center;"><span style="color: #5a5c66;">Not many people know what it\'s like to travel all over the world and perform every night in many different places</span></p>',
                  ),
                  2 =>
                  array (
                    'type' => 'button',
                    'text' => 'Find out more',
                    'url' => '',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#4c537e',
                        'borderColor' => '#0074a2',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '95px',
                        'lineHeight' => '30px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '12px',
                        'fontWeight' => 'normal',
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
                'backgroundColor' => '#27282e',
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
                    'text' => '<h3 style="text-align: center;"><span style="color: #ffffff;"><strong>New tour dates</strong></span></h3>
    <p style="text-align: center;"><span style="color: #999999;">Come see me perform new tracks from the album</span></p>',
                  ),
                  2 =>
                  array (
                    'type' => 'divider',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => 'transparent',
                        'padding' => '14px',
                        'borderStyle' => 'dashed',
                        'borderWidth' => '2px',
                        'borderColor' => '#37383e',
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
                'backgroundColor' => '#27282e',
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
                    'type' => 'button',
                    'text' => 'March 7th 2019',
                    'url' => '',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#4c537e',
                        'borderColor' => '#0074a2',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '288px',
                        'lineHeight' => '40px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '16px',
                        'fontWeight' => 'normal',
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<p style="text-align: center;"><span style="color: #ffffff;">Metro Radio Arena - Newcastle Upon Tyne</span></p>',
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
                    'type' => 'button',
                    'text' => 'March 8th 2019',
                    'url' => '',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#4c537e',
                        'borderColor' => '#0074a2',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '288px',
                        'lineHeight' => '40px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '16px',
                        'fontWeight' => 'normal',
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<p style="text-align: center;"><span style="color: #ffffff;">First Direct Arena</span></p>
    <p style="text-align: center;"><span style="color: #ffffff;">- Leeds</span></p>',
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
                    'type' => 'button',
                    'text' => 'March 9th 2019',
                    'url' => '',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#4c537e',
                        'borderColor' => '#0074a2',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '288px',
                        'lineHeight' => '40px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '16px',
                        'fontWeight' => 'normal',
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<p style="text-align: center;"><span style="color: #ffffff;">Liverpool Echo Arena - Liverpool</span></p>',
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
                'backgroundColor' => '#27282e',
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
              'src' => NULL,
              'display' => 'scale',
            ),
            'styles' =>
            array (
              'block' =>
              array (
                'backgroundColor' => '#27282e',
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
                    'type' => 'button',
                    'text' => 'March 11th 2019',
                    'url' => '',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#4c537e',
                        'borderColor' => '#0074a2',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '288px',
                        'lineHeight' => '40px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '16px',
                        'fontWeight' => 'normal',
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<p style="text-align: center;"><span style="color: #ffffff;">The Brighton Centre - Brighton</span></p>',
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
                    'type' => 'button',
                    'text' => 'March 12th 2019',
                    'url' => '',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#4c537e',
                        'borderColor' => '#0074a2',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '288px',
                        'lineHeight' => '40px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '16px',
                        'fontWeight' => 'normal',
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<p style="text-align: center;"><span style="color: #ffffff;">Motorpoint Arena Nottingham - Nottingham</span></p>',
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
                    'type' => 'button',
                    'text' => 'March 13th 2019',
                    'url' => '',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#4c537e',
                        'borderColor' => '#0074a2',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '288px',
                        'lineHeight' => '40px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '16px',
                        'fontWeight' => 'normal',
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<p style="text-align: center;"><span style="color: #ffffff;">Motorpoint Arena Cardiff - Cardiff</span></p>',
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
                'backgroundColor' => '#27282e',
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
                        'padding' => '14px',
                        'borderStyle' => 'dashed',
                        'borderWidth' => '2px',
                        'borderColor' => '#37383e',
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
                        'height' => '40px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          11 =>
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
                'backgroundColor' => '#27282e',
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
                    'src' => $this->template_image_url . '/Guitarist-Logo.png',
                    'alt' => 'Guitarist-Logo',
                    'fullWidth' => true,
                    'width' => '176px',
                    'height' => '179px',
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
                    'type' => 'footer',
                    'text' => '<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage subscription</a><br /><span style="color: #999999;">Add your postal address here!</span></p>',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => 'transparent',
                      ),
                      'text' =>
                      array (
                        'fontColor' => '#222222',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '12px',
                        'textAlign' => 'center',
                      ),
                      'link' =>
                      array (
                        'fontColor' => '#4c537e',
                        'textDecoration' => 'none',
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
          'fontColor' => '#27282e',
          'fontFamily' => 'Merriweather Sans',
          'fontSize' => '15px',
        ),
        'h1' =>
        array (
          'fontColor' => '#27282e',
          'fontFamily' => 'Merriweather Sans',
          'fontSize' => '30px',
        ),
        'h2' =>
        array (
          'fontColor' => '#27282e',
          'fontFamily' => 'Merriweather Sans',
          'fontSize' => '24px',
        ),
        'h3' =>
        array (
          'fontColor' => '#ffffff',
          'fontFamily' => 'Merriweather Sans',
          'fontSize' => '22px',
        ),
        'link' =>
        array (
          'fontColor' => '#21759B',
          'textDecoration' => 'underline',
        ),
        'wrapper' =>
        array (
          'backgroundColor' => '#ffffff',
        ),
        'body' =>
        array (
          'backgroundColor' => '#27282e',
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
          'text' => 'March 13th 2019',
          'url' => '',
          'styles' =>
          array (
            'block' =>
            array (
              'backgroundColor' => '#4c537e',
              'borderColor' => '#0074a2',
              'borderWidth' => '0px',
              'borderRadius' => '0px',
              'borderStyle' => 'solid',
              'width' => '288px',
              'lineHeight' => '40px',
              'fontColor' => '#ffffff',
              'fontFamily' => 'Merriweather Sans',
              'fontSize' => '16px',
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
              'padding' => '14px',
              'borderStyle' => 'dashed',
              'borderWidth' => '2px',
              'borderColor' => '#37383e',
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
              'fontFamily' => 'Merriweather Sans',
              'fontSize' => '12px',
              'textAlign' => 'center',
            ),
            'link' =>
            array (
              'fontColor' => '#4c537e',
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
              'iconType' => 'instagram',
              'link' => 'http://instagram.com',
              'image' => $this->social_icon_url . '/08-full-symbol-grey/Instagram.png',
              'height' => '32px',
              'width' => '32px',
              'text' => 'Instagram',
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
              'fontFamily' => 'Merriweather Sans',
              'fontSize' => '12px',
              'textAlign' => 'center',
            ),
            'link' =>
            array (
              'fontColor' => '#949494',
              'textDecoration' => 'underline',
            ),
          ),
          'type' => 'header',
        ),
      ),
    );
  }

}
