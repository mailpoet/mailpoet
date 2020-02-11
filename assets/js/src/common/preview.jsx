import React, { useState } from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

function Preview({
  children,
}) {
  const [checked, setChecked] = useState('desktop');
  return (
    <div className="mailpoet_browser_preview">
      <div className="mailpoet_browser_preview_toggle">
        <label>
          <input
            type="radio"
            name="mailpoet_browser_preview_type"
            className="mailpoet_browser_preview_type"
            checked={checked === 'desktop'}
            onChange={() => setChecked('desktop')}
          />
          {MailPoet.I18n.t('formPreviewDesktop')}
        </label>
        <label>
          <input
            type="radio"
            name="mailpoet_browser_preview_type"
            className="mailpoet_browser_preview_type"
            checked={checked !== 'desktop'}
            onChange={() => setChecked('mobile')}
          />
          {MailPoet.I18n.t('formPreviewMobile')}
        </label>
      </div>
      {children}
    </div>
  );
}

Preview.propTypes = {
  children: PropTypes.node.isRequired,
};

export default Preview;
