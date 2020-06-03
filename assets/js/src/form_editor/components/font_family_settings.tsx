import React from 'react';
import {
  CustomSelectControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import MailPoet from 'mailpoet';

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
  const customFonts = useSelect(
    (select) => select('mailpoet-form-editor').getAllCustomFonts(),
    []
  );
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

export const CustomFontsStyleSheetLink = () => {
  const customFonts = useSelect(
    (select) => select('mailpoet-form-editor').getAllCustomFonts(),
    []
  );
  const customFontsUrl = customFonts
    .map((fontName) => fontName.replace(' ', '+'))
    .map((fontName) => fontName.concat(':400,400i,700,700'))
    .join('|');
  return (
    <link
      rel="stylesheet"
      href={`https://fonts.googleapis.com/css?family=${customFontsUrl}`}
    />
  );
};
