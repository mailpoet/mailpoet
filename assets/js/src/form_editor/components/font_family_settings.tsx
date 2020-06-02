import React from 'react';
import {
  CustomSelectControl,
} from '@wordpress/components';
import MailPoet from 'mailpoet';

export const customFonts = [
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

type Props = {
  onChange: (value: string|undefined) => any,
  value?: string,
  name: string,
}

const FontFamilySettings = ({
  onChange,
  value,
  name,
}: Props) => {
  const disabledStyle = {
    color: 'lightgray',
    backgroundColor: 'white',
    cursor: 'default',
  };
  const getFontStyle = (fontName) => ({
    fontFamily: fontName,
    cursor: 'default',
    marginLeft: 16,
  });
  const options = [
    {
      key: MailPoet.I18n.t('formFontsDefaultTheme'),
      name: MailPoet.I18n.t('formFontsDefaultTheme'),
      selectable: true,
    },
    {
      key: MailPoet.I18n.t('formFontsStandard'),
      name: MailPoet.I18n.t('formFontsStandard'),
      selectable: false,
      style: disabledStyle,
    },
    ...standardFonts.map((fontName) => ({
      key: fontName,
      name: fontName,
      selectable: true,
      style: getFontStyle(fontName),
    })),
    {
      key: MailPoet.I18n.t('formFontsCustom'),
      name: MailPoet.I18n.t('formFontsCustom'),
      selectable: false,
      style: disabledStyle,
    },
    ...customFonts.map((fontName) => ({
      key: fontName,
      name: fontName,
      selectable: true,
      style: getFontStyle(fontName),
    })),
  ];

  let selectedValue = options.find((item) => item.name === value);
  if (!selectedValue) {
    selectedValue = options[0];
  }
  return (
    <CustomSelectControl
      options={options}
      onChange={(selected) => {
        if (selected.selectedItem.selectable) {
          onChange(selected.selectedItem.name);
        }
      }}
      value={selectedValue}
      label={name}
      className="mailpoet-font-family-select"
    />
  );
};

export default FontFamilySettings;

const customFontsUrl = customFonts
  .map((fontName) => fontName.replace(' ', '+'))
  .map((fontName) => fontName.concat(':400,400i,700,700'))
  .join('|');

export const CustomFontsStyleSheetLink = () => (
  <link
    rel="stylesheet"
    href={`https://fonts.googleapis.com/css?family=${customFontsUrl}`}
  />
);
