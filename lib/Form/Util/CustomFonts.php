<?php

namespace MailPoet\Form\Util;

use MailPoet\WP\Functions;

class CustomFonts {
  const FONTS = [
    'Abril FatFace',
    'Alegreya',
    'Alegreya Sans',
    'Amatic SC',
    'Anonymous Pro',
    'Architects Daughter',
    'Archivo',
    'Archivo Narrow',
    'Asap',
    'Barlow',
    'BioRhyme',
    'Bonbon',
    'Cabin',
    'Cairo',
    'Cardo',
    'Chivo',
    'Concert One',
    'Cormorant',
    'Crimson Text',
    'Eczar',
    'Exo 2',
    'Fira Sans',
    'Fjalla One',
    'Frank Ruhl Libre',
    'Great Vibes',
    'Heebo',
    'IBM Plex',
    'Inconsolata',
    'Indie Flower',
    'Inknut Antiqua',
    'Inter',
    'Karla',
    'Libre Baskerville',
    'Libre Franklin',
    'Montserrat',
    'Neuton',
    'Notable',
    'Nothing You Could Do',
    'Noto Sans',
    'Nunito',
    'Old Standard TT',
    'Oxygen',
    'Pacifico',
    'Poppins',
    'Proza Libre',
    'PT Sans',
    'PT Serif',
    'Rakkas',
    'Reenie Beanie',
    'Roboto Slab',
    'Ropa Sans',
    'Rubik',
    'Shadows Into Light',
    'Space Mono',
    'Spectral',
    'Sue Ellen Francisco',
    'Titillium Web',
    'Ubuntu',
    'Varela',
    'Vollkorn',
    'Work Sans',
    'Yatra One',
  ];

  /** @var Functions */
  private $wp;

  public function __construct(Functions $wp) {
    $this->wp = $wp;
  }

  public function enqueueStyle() {
    $displayCustomFonts = $this->wp->applyFilters('mailpoet_display_custom_fonts', true);
    if ($displayCustomFonts) {
      $this->wp->wpEnqueueStyle('mailpoet_custom_fonts_css', $this->generateLink());
    }
  }

  private function generateLink(): string {
    $fonts = array_map(function ($fontName) {
      return urlencode($fontName) . ':400,400i,700,700i';
    }, self::FONTS);
    $fonts = implode('|', $fonts);
    return 'https://fonts.googleapis.com/css?family=' . $fonts;
  }
}
