import React from 'react';
import { createPortal } from 'react-dom';
import Mailpoet from 'mailpoet';
import Select, { Props as ReactSelectProps } from './react_select';

export type Props = ReactSelectProps & {
  customFontsElementId?: string,
  displayCustomFontsStylesheet?: boolean,
  onChange: (value: any) => void,
};

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
  customFontsElementId,
  displayCustomFontsStylesheet = true,
  onChange,
  ...props
}: Props) => {
  const fonts = [
    {
      label: 'Theme’s default fonts',
      value: undefined,
    },
    {
      label: 'Standard fonts',
      value: '',
      options: [],
    },
    {
      label: 'Custom fonts',
      value: '',
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

  standardFonts.forEach((fontName) => fonts[1].options.push(buildOption(fontName)));
  customFonts.forEach((fontName) => fonts[2].options.push(buildOption(fontName)));

  let link;
  if (displayCustomFontsStylesheet) {
    const customFontsUrl = customFonts
      .map((fontName) => fontName.replace(' ', '+'))
      .join('|');
    link = (
      <link
        rel="stylesheet"
        href={`https://fonts.googleapis.com/css?family=${customFontsUrl}`}
      />
    );

    if (customFontsElementId) {
      createPortal(link, document.getElementById(customFontsElementId));
      link = undefined;
    }
  }

  return (
    <>
      {link}
      <Select
        options={fonts}
        onChange={(newValue) => {
          // typescript yells at me for no apparent reason, I don't know why this needs to be here
          // eslint-disable-next-line @typescript-eslint/ban-ts-ignore
          // @ts-ignore
          onChange(newValue.value);
        }}
        {...props} // eslint-disable-line react/jsx-props-no-spreading
      />
    </>
  );
};

export default FontSelect;
