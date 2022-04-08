import { applyFormat } from '@wordpress/rich-text';
import MailPoet from 'mailpoet';
import { BlockFormatControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

import FontFamilySettings from '../components/font_family_settings';

const name = 'mailpoet-form/font-selection';
const title = 'Font Selection';

const supportedBlocks = ['core/paragraph', 'core/heading'];

type Attributes = {
  font?: string;
  style?: string;
};

type Format = {
  attributes?: Attributes;
  type?: string;
  unregisteredAttributes?: Attributes;
};

type Value = {
  formats: Format[][];
  replacements: (string | object)[];
  text: string;
  activeFormats?: Format[];
  start?: number;
  end?: number;
};

type Props = {
  value: Value;
  onChange: (object) => void;
  activeAttributes: {
    font?: string;
  };
};

function Edit({ value, onChange, activeAttributes }: Props): JSX.Element {
  const selectedBlock = useSelect(
    (sel) => sel('core/block-editor').getSelectedBlock(),
    [],
  );

  if (!supportedBlocks.includes(selectedBlock.name as string)) {
    return null;
  }

  return (
    <BlockFormatControls>
      <div className="mailpoet_toolbar_item">
        <FontFamilySettings
          value={activeAttributes.font}
          onChange={(font): void => {
            onChange(
              applyFormat(value, {
                type: 'mailpoet-form/font-selection',
                attributes: {
                  style: `font-family: ${font}`,
                  font,
                },
              }),
            );
          }}
          name={MailPoet.I18n.t('formSettingsStylesFontFamily')}
          hideLabelFromVision
        />
      </div>
    </BlockFormatControls>
  );
}

const settings = {
  name,
  title,
  tagName: 'span',
  className: 'mailpoet-has-font',
  attributes: {
    style: 'style',
    font: 'data-font',
  },
  edit: Edit,
};

export { name, settings };
