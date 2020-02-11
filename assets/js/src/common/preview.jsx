import React, { useState } from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import classnames from 'classnames';

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
      <div
        className={classnames(
          'mailpoet_browser_preview_container',
          { mailpoet_browser_preview_container_mobile: checked !== 'desktop' },
          { mailpoet_browser_preview_container_desktop: checked === 'desktop' },
        )}
      >
        <div className="mailpoet_browser_preview_border">
          {children}
        </div>
      </div>
    </div>
  );
}

Preview.propTypes = {
  children: PropTypes.node.isRequired,
};

export default Preview;
