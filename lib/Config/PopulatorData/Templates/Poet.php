<?php

namespace MailPoet\Config\PopulatorData\Templates;

class Poet {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/poet';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Poet", 'mailpoet'),
      'categories' => json_encode(array('welcome', 'all')),
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
                'backgroundColor' => '#ebebeb',
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
          1 =>
          array (
            'type' => 'container',
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' =>
            array (
              'src' => $this->template_image_url . '/Poet-Header.jpg',
              'display' => 'scale',
            ),
            'styles' =>
            array (
              'block' =>
              array (
                'backgroundColor' => '#ebebeb',
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
                    'type' => 'header',
                    'text' => '<p><a href="[link:newsletter_view_in_browser_url]">View email in browser &gt;</a></p>',
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
                        'textAlign' => 'left',
                      ),
                      'link' =>
                      array (
                        'fontColor' => '#909090',
                        'textDecoration' => 'none',
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
                        'height' => '144px',
                      ),
                    ),
                  ),
                  2 =>
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Poet-Logo.png',
                    'alt' => 'Poet-Logo',
                    'fullWidth' => false,
                    'width' => '166px',
                    'height' => '144px',
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
                  4 =>
                  array (
                    'type' => 'text',
                    'text' => '<h1>Thanks for signing up!</h1>
    <p><span style="color: #000000;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis ut fringilla velit, id malesuada nisi. Nam ac rutrum diam.</span></p>',
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
                ),
              ),
            ),
          ),
          2 =>
          array (
            'type' => 'container',
            'columnLayout' => '2_1',
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
                        'height' => '66px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<p style="font-size: 12px;"><span style="color: #999999;"><strong>Latest p</strong></span></p>
    <h2><strong>The 26th of December</strong></h2>
    <h3><span>A Tuesday, day of Tiw,</span><br /><span>god of war, dawns in darkness.</span><br /><span>The short holiday day of talking by the fire,</span><br /><span>floating on snowshoes among</span><br /><span>ancient self-pollarded maples,</span><br /><span>visiting, being visited, giving</span><br /><span>a rain gauge, receiving red socks,</span><br /><span>watching snow buntings nearly over</span><br /><span>their heads in snow stab at spirtled bits</span><br /><span>of sunflower seeds the chickadees</span><br /><span>hold with their feet to a bough</span><br /><span>and hack apart, scattering debris</span><br /><span>like sloppy butchers, is over.</span><br /><span>Irregular life begins. Telephone calls,</span><br /><span>Google searches, evasive letters,</span><br /><span>complicated arrangements, faxes,</span><br /><span>second thoughts, consultations,</span><br /><span>e-mails, solemnly given kisses.</span></h3>',
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
                    'src' => $this->template_image_url . '/Poet-Images-1.jpg',
                    'alt' => 'Poet-Images',
                    'fullWidth' => true,
                    'width' => '400px',
                    'height' => '1200px',
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
                'backgroundColor' => '#393939',
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
                    'text' => '<p style="text-align: right;"><span style="color: #ffffff;">What did you think of this poem? Let me know on one of my social media channels.</span></p>',
                  ),
                  2 =>
                  array (
                    'type' => 'social',
                    'iconSet' => 'full-symbol-grey',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'textAlign' => 'right',
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
                        'height' => '40px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<h1 style="text-align: center;">Poetry books</h1>
    <p style="text-align: center;"><span>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis ut fringilla velit, id malesuada nisi. Nam ac rutrum diam.</span></p>',
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
                    'src' => $this->template_image_url . '/Poet-Books-1.png',
                    'alt' => 'Poet-Books-1',
                    'fullWidth' => false,
                    'width' => '220px',
                    'height' => '646px',
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
                    'type' => 'button',
                    'text' => 'Buy online',
                    'url' => '',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#393939',
                        'borderColor' => '#0074a2',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '180px',
                        'lineHeight' => '40px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '14px',
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
                    'src' => $this->template_image_url . '/Poet-Books-3.png',
                    'alt' => 'Poet-Books-3',
                    'fullWidth' => false,
                    'width' => '600px',
                    'height' => '646px',
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
                    'type' => 'button',
                    'text' => 'Buy online',
                    'url' => '',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#393939',
                        'borderColor' => '#0074a2',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '180px',
                        'lineHeight' => '40px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '14px',
                        'fontWeight' => 'normal',
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
                    'src' => $this->template_image_url . '/Poet-Books-2.png',
                    'alt' => 'Poet-Books-2',
                    'fullWidth' => false,
                    'width' => '600px',
                    'height' => '646px',
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
                    'type' => 'button',
                    'text' => 'Buy online',
                    'url' => '',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#393939',
                        'borderColor' => '#0074a2',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '180px',
                        'lineHeight' => '40px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '14px',
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
                    'src' => $this->template_image_url . '/Poet-Logo.png',
                    'alt' => 'Poet-Logo',
                    'fullWidth' => false,
                    'width' => '166px',
                    'height' => '144px',
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
                    'type' => 'footer',
                    'text' => '<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage subscription</a><br />Add your postal address here!</p>',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => 'transparent',
                      ),
                      'text' =>
                      array (
                        'fontColor' => '#939393',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '12px',
                        'textAlign' => 'right',
                      ),
                      'link' =>
                      array (
                        'fontColor' => '#333333',
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
          'fontColor' => '#646464',
          'fontFamily' => 'Merriweather Sans',
          'fontSize' => '14px',
        ),
        'h1' =>
        array (
          'fontColor' => '#393939',
          'fontFamily' => 'Merriweather',
          'fontSize' => '24px',
        ),
        'h2' =>
        array (
          'fontColor' => '#393939',
          'fontFamily' => 'Merriweather',
          'fontSize' => '30px',
        ),
        'h3' =>
        array (
          'fontColor' => '#646464',
          'fontFamily' => 'Merriweather Sans',
          'fontSize' => '16px',
        ),
        'link' =>
        array (
          'fontColor' => '#393939',
          'textDecoration' => 'underline',
        ),
        'wrapper' =>
        array (
          'backgroundColor' => '#ebebeb',
        ),
        'body' =>
        array (
          'backgroundColor' => '#ebebeb',
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
          'text' => 'Buy online',
          'url' => '',
          'styles' =>
          array (
            'block' =>
            array (
              'backgroundColor' => '#393939',
              'borderColor' => '#0074a2',
              'borderWidth' => '0px',
              'borderRadius' => '0px',
              'borderStyle' => 'solid',
              'width' => '180px',
              'lineHeight' => '40px',
              'fontColor' => '#ffffff',
              'fontFamily' => 'Merriweather Sans',
              'fontSize' => '14px',
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
              'fontColor' => '#939393',
              'fontFamily' => 'Merriweather Sans',
              'fontSize' => '12px',
              'textAlign' => 'right',
            ),
            'link' =>
            array (
              'fontColor' => '#333333',
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
          'styles' =>
          array (
            'block' =>
            array (
              'textAlign' => 'right',
            ),
          ),
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
              'fontFamily' => 'Merriweather Sans',
              'fontSize' => '12px',
              'textAlign' => 'left',
            ),
            'link' =>
            array (
              'fontColor' => '#909090',
              'textDecoration' => 'none',
            ),
          ),
          'type' => 'header',
        ),
      ),
    );
  }

}
