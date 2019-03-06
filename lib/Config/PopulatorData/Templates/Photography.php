<?php

namespace MailPoet\Config\PopulatorData\Templates;

class Photography {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/photography';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Photography", 'mailpoet'),
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
                        'height' => '40px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Photography-Logo.png',
                    'alt' => 'Photography-Logo',
                    'fullWidth' => false,
                    'width' => '160px',
                    'height' => '141px',
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
                        'height' => '42px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<p style="text-align: right;"><a href="[link:newsletter_view_in_browser_url]">View online &gt;</a></p>',
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
                        'height' => '30px',
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
                    'text' => '<h1 style="line-height: 48px;"><strong>The big photos from last week, straight to your inbox.</strong></h1>',
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Photography-Images-1.jpg',
                    'alt' => 'Photography-Images-1',
                    'fullWidth' => true,
                    'width' => '1200px',
                    'height' => '800px',
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
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<p><strong><span style="color: #000000;">Canyon</span></strong></p>
    <p><span style="color: #000000;">📷&nbsp;Brodie Vissers</span></p>',
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
                        'height' => '25px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'button',
                    'text' => 'View all work',
                    'url' => '',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#111111',
                        'borderColor' => '#0074a2',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '140px',
                        'lineHeight' => '40px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Courier New',
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
                        'height' => '60px',
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Photography-Images-2.jpg',
                    'alt' => 'Photography-Images-2',
                    'fullWidth' => true,
                    'width' => '1200px',
                    'height' => '800px',
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
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<p><strong><span style="color: #000000;">Rooftopper</span></strong></p>
    <p><span style="color: #000000;">📷 Stan Johnson</span></p>',
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
                        'height' => '25px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'button',
                    'text' => 'View all work',
                    'url' => '',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#111111',
                        'borderColor' => '#0074a2',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '140px',
                        'lineHeight' => '40px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Courier New',
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
                        'height' => '60px',
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Photography-Images-3.jpg',
                    'alt' => 'Photography-Images-3',
                    'fullWidth' => true,
                    'width' => '1200px',
                    'height' => '800px',
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
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<p><strong><span style="color: #000000;">Gold</span></strong></p>
    <p><span style="color: #000000;">📷 Nicole De Khors</span></p>',
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
                        'height' => '25px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'button',
                    'text' => 'View all work',
                    'url' => '',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#111111',
                        'borderColor' => '#0074a2',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '140px',
                        'lineHeight' => '40px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Courier New',
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
          12 =>
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
                        'height' => '60px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          13 =>
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
                    'text' => '<h1 style="line-height: 48px; text-align: center;"><strong>Fresh inspiration</strong></h1>',
                  ),
                ),
              ),
            ),
          ),
          14 =>
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
                    'src' => $this->template_image_url . '/Photography-Squares-1.jpg',
                    'alt' => 'Photography-Squares-1',
                    'fullWidth' => true,
                    'width' => '700px',
                    'height' => '700px',
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Photography-Squares-2.jpg',
                    'alt' => 'Photography-Squares-2',
                    'fullWidth' => true,
                    'width' => '700px',
                    'height' => '700px',
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
                    'src' => $this->template_image_url . '/Photography-Squares-3.jpg',
                    'alt' => 'Photography-Squares-3',
                    'fullWidth' => true,
                    'width' => '700px',
                    'height' => '700px',
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
          15 =>
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
                    'src' => $this->template_image_url . '/Photography-Squares-4.jpg',
                    'alt' => 'Photography-Squares-4',
                    'fullWidth' => true,
                    'width' => '700px',
                    'height' => '700px',
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Photography-Squares-5.jpg',
                    'alt' => 'Photography-Squares-5',
                    'fullWidth' => true,
                    'width' => '700px',
                    'height' => '700px',
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
                    'src' => $this->template_image_url . '/Photography-Squares-6.jpg',
                    'alt' => 'Photography-Squares-6',
                    'fullWidth' => true,
                    'width' => '700px',
                    'height' => '700px',
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
          16 =>
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
                    'src' => $this->template_image_url . '/Photography-Squares-7.jpg',
                    'alt' => 'Photography-Squares-7',
                    'fullWidth' => true,
                    'width' => '700px',
                    'height' => '700px',
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Photography-Squares-8.jpg',
                    'alt' => 'Photography-Squares-8',
                    'fullWidth' => true,
                    'width' => '700px',
                    'height' => '700px',
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
                    'src' => $this->template_image_url . '/Photography-Squares-9.jpg',
                    'alt' => 'Photography-Squares-9',
                    'fullWidth' => true,
                    'width' => '700px',
                    'height' => '700px',
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
          17 =>
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
                        'height' => '60px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          18 =>
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
                    'text' => '<h1 style="line-height: 48px; text-align: center;"><strong>Stay focused</strong></h1>
    <p style="text-align: center;">We\'re always keeping our social media feeds up to date with the latest images from our photographers. Check them out below.</p>',
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
          19 =>
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
                    'type' => 'divider',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => 'transparent',
                        'padding' => '29px',
                        'borderStyle' => 'solid',
                        'borderWidth' => '2px',
                        'borderColor' => '#2d2d2d',
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
                    'src' => $this->template_image_url . '/Photography-Logo.png',
                    'alt' => 'Photography-Logo',
                    'fullWidth' => false,
                    'width' => '108px',
                    'height' => '141px',
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
                    'text' => '<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage subscription</a><br /><span style="color: #999999;">Add your postal address here!</span></p>',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => 'transparent',
                      ),
                      'text' =>
                      array (
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Courier New',
                        'fontSize' => '13px',
                        'textAlign' => 'center',
                      ),
                      'link' =>
                      array (
                        'fontColor' => '#ffffff',
                        'textDecoration' => 'underline',
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
        ),
      ),
      'globalStyles' =>
      array (
        'text' =>
        array (
          'fontColor' => '#ffffff',
          'fontFamily' => 'Courier New',
          'fontSize' => '16px',
        ),
        'h1' =>
        array (
          'fontColor' => '#ffffff',
          'fontFamily' => 'Merriweather Sans',
          'fontSize' => '40px',
        ),
        'h2' =>
        array (
          'fontColor' => '#ffffff',
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
          'fontColor' => '#ffffff',
          'textDecoration' => 'underline',
        ),
        'wrapper' =>
        array (
          'backgroundColor' => '#111111',
        ),
        'body' =>
        array (
          'backgroundColor' => '#111111',
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
          'text' => 'View all work',
          'url' => '',
          'styles' =>
          array (
            'block' =>
            array (
              'backgroundColor' => '#111111',
              'borderColor' => '#0074a2',
              'borderWidth' => '0px',
              'borderRadius' => '0px',
              'borderStyle' => 'solid',
              'width' => '140px',
              'lineHeight' => '40px',
              'fontColor' => '#ffffff',
              'fontFamily' => 'Courier New',
              'fontSize' => '14px',
              'fontWeight' => 'normal',
              'textAlign' => 'right',
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
              'padding' => '29px',
              'borderStyle' => 'solid',
              'borderWidth' => '2px',
              'borderColor' => '#2d2d2d',
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
              'fontColor' => '#ffffff',
              'fontFamily' => 'Courier New',
              'fontSize' => '13px',
              'textAlign' => 'center',
            ),
            'link' =>
            array (
              'fontColor' => '#ffffff',
              'textDecoration' => 'underline',
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
              'height' => '20px',
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
              'textAlign' => 'center',
            ),
            'link' =>
            array (
              'fontColor' => '#6cb7d4',
              'textDecoration' => 'underline',
            ),
          ),
        ),
      ),
    );
  }

}
