import React from 'react';
import { applyFormat } from '@wordpress/rich-text';
import MailPoet from 'mailpoet';
import { Fill } from '@wordpress/components';

import FontFamilySettings from '../components/font_family_settings';

const name = 'mailpoet-form/font-selection';
const title = 'Font Selection';

const settings = {
  name,
  title,
  tagName: 'span',
  className: 'has-font',
  attributes: {
    style: 'style',
    font: 'data-font',
  },
  edit({
    value,
    onChange,
    activeAttributes,
  }) {
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
  },
};

export { name, settings };
