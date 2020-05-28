import React from 'react';
import Select, { Props } from './react_select';

const customFonts = [
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

const standardFonts = [
  'Arial',
  'Comic Sans MS',
  'Courier New',
  'Georgia',
  'Lucida',
  'Tahoma',
  'Times New Roman',
  'Trebuchet MS',
  'Verdana',
];

const FontSelect = ({
  ...props
}: Props) => {
  const fonts = [
    {
      label: 'standard',
      options: [],
    },
    {
      label: 'custom',
      options: [],
    },
  ];

  const buildOption = (fontName) => ({
    label: fontName,
    value: fontName,
    style: {
      fontFamily: fontName,
    },
  });

  standardFonts.forEach((fontName) => fonts[0].options.push(buildOption(fontName)));
  customFonts.forEach((fontName) => fonts[1].options.push(buildOption(fontName)));

  return (
    <Select
      options={fonts}
      {...props} // eslint-disable-line react/jsx-props-no-spreading
    />
  );
};

export default FontSelect;
