import { CSSProperties } from 'react';
import { CustomSelectControl } from '@wordpress/components';
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
  onChange: (value: string | undefined) => void;
  value?: string;
  name: string;
  hideLabelFromVision?: boolean;
};

function FontFamilySettings({
  onChange,
  value,
  name,
  hideLabelFromVision = false,
}: Props): JSX.Element {
  const customFonts = useSelect(
    (select) => select('mailpoet-form-editor').getAllCustomFonts(),
    [],
  );
  const disabledStyle = {
    color: 'lightgray',
    backgroundColor: 'white',
    cursor: 'default',
  };
  const getFontStyle = (fontName): CSSProperties => ({
    fontFamily: fontName,
    cursor: 'default',
    marginLeft: 16,
  });
  const options = [
    {
      key: MailPoet.I18n.t('formFontsDefaultTheme'),
      name: MailPoet.I18n.t('formFontsDefaultTheme'),
      selectable: true,
      value: '',
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
      value: fontName,
    })),
  ];
  if (MailPoet.libs3rdPartyEnabled) {
    options.push({
      key: MailPoet.I18n.t('formFontsCustom'),
      name: MailPoet.I18n.t('formFontsCustom'),
      selectable: false,
      style: disabledStyle,
    });
    customFonts.forEach((fontName) => {
      options.push({
        key: fontName,
        name: fontName,
        selectable: true,
        style: getFontStyle(fontName),
        value: fontName,
      });
    });
  }
  let selectedValue =
    value !== undefined && options.find((item) => item.value === value);
  if (!selectedValue) {
    selectedValue = options[0];
  }
  return (
    // CustomSelectControl generates a warning in the console. See [MAILPOET-3399]
    <CustomSelectControl
      options={options}
      onChange={(selected): void => {
        if (selected.selectedItem.selectable) {
          onChange(selected.selectedItem.value as string);
        }
      }}
      value={selectedValue}
      label={name}
      className="mailpoet-font-family-select"
      hideLabelFromVision={hideLabelFromVision}
    />
  );
}

export default FontFamilySettings;

export function CustomFontsStyleSheetLink(): JSX.Element {
  const customFonts = useSelect(
    (select) => select('mailpoet-form-editor').getAllCustomFonts(),
    [],
  );
  if (!MailPoet.libs3rdPartyEnabled) {
    return null;
  }
  const customFontsUrl = customFonts
    .map((fontName) => fontName.replace(' ', '+'))
    .map((fontName) => fontName.concat(':400,400i,700,700i'))
    .join('|');
  return (
    <link
      rel="stylesheet"
      href={`https://fonts.googleapis.com/css?family=${customFontsUrl}`}
    />
  );
}
