import React, { useState } from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import classnames from 'classnames';
import MobileIcon from './mobile_icon';
import DesktopIcon from './desktop_icon';

function Preview({
  children,
  onChange,
  selectedType,
}) {
  const [checked, setChecked] = useState(selectedType);
  const changeType = (type) => {
    setChecked(type);
    onChange(type);
  };
  return (
    <div className="mailpoet_browser_preview">
      <div className="mailpoet_browser_preview_toggle">
        <a
          className={classnames('mailpoet_browser_preview_icon', { mailpoet_active: checked === 'desktop' })}
          onClick={(e) => {
            e.preventDefault();
            changeType('desktop');
          }}
          title={MailPoet.I18n.t('formPreviewDesktop')}
          href="#"
        >
          <DesktopIcon />
        </a>
        <a
          className={classnames('mailpoet_browser_preview_icon', { mailpoet_active: checked === 'mobile' })}
          onClick={(e) => {
            e.preventDefault();
            changeType('mobile');
          }}
          title={MailPoet.I18n.t('formPreviewMobile')}
          href="#"
        >
          <MobileIcon />
        </a>
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
  onChange: PropTypes.func,
  selectedType: PropTypes.string,
};

Preview.defaultProps = {
  onChange: () => {},
  selectedType: 'desktop',
};

export default Preview;
