import React from 'react';
import { applyFormat } from '@wordpress/rich-text';
import MailPoet from 'mailpoet';
import { Fill } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

import FontFamilySettings from '../components/font_family_settings';

const name = 'mailpoet-form/font-selection';
const title = 'Font Selection';

const supportedBlocks = [
  'core/paragraph',
  'core/heading',
];

const Edit = ({
  value,
  onChange,
  activeAttributes,
}) => {
  const selectedBlock = useSelect(
    (sel) => sel('core/block-editor').getSelectedBlock(),
    []
  );

  if (!supportedBlocks.includes(selectedBlock.name)) {
    return null;
  }

  return (
    <>
      <Fill name="BlockFormatControls">
        <div className="mailpoet_toolbar_item">
          <FontFamilySettings
            value={activeAttributes.font}
            onChange={(font) => {
              onChange(
                applyFormat(value, {
                  type: 'mailpoet-form/font-selection',
                  attributes: {
                    style: `font-family: ${font}`,
                    font,
                  },
                })
              );
            }}
            name={MailPoet.I18n.t('formSettingsStylesFontFamily')}
            hideLabelFromVision
          />
        </div>
      </Fill>
    </>
  );
};

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
