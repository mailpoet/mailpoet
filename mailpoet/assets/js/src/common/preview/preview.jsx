import { useState } from 'react';
import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import classnames from 'classnames';
import { MobileIcon } from './mobile_icon';
import { DesktopIcon } from './desktop_icon';

function Preview({ children, onDisplayTypeChange, selectedDisplayType }) {
  const [displayType, setDisplayType] = useState(selectedDisplayType);
  const changeType = (type) => {
    setDisplayType(type);
    onDisplayTypeChange(type);
  };
  return (
    <div className="mailpoet_browser_preview">
      <div className="mailpoet_browser_preview_toggle">
        <a
          className={classnames('mailpoet_browser_preview_icon', {
            mailpoet_active: displayType === 'desktop',
          })}
          onClick={(e) => {
            e.preventDefault();
            changeType('desktop');
          }}
          title={MailPoet.I18n.t('formPreviewDesktop')}
          href="#"
          data-automation-id="preview_type_desktop"
        >
          <DesktopIcon />
        </a>
        <a
          className={classnames('mailpoet_browser_preview_icon', {
            mailpoet_active: displayType === 'mobile',
          })}
          onClick={(e) => {
            e.preventDefault();
            changeType('mobile');
          }}
          title={MailPoet.I18n.t('formPreviewMobile')}
          href="#"
          data-automation-id="preview_type_mobile"
        >
          <MobileIcon />
        </a>
      </div>
      <div
        className={classnames(
          'mailpoet_browser_preview_container',
          {
            mailpoet_browser_preview_container_mobile:
              displayType !== 'desktop',
          },
          {
            mailpoet_browser_preview_container_desktop:
              displayType === 'desktop',
          },
        )}
      >
        <div className="mailpoet_browser_preview_border">{children}</div>
      </div>
      {displayType !== 'desktop' && (
        <p className="mailpoet_form_preview_disclaimer">
          {MailPoet.I18n.t('formPreviewMobileDisclaimer')}
        </p>
      )}
    </div>
  );
}

Preview.propTypes = {
  children: PropTypes.node.isRequired,
  onDisplayTypeChange: PropTypes.func,
  selectedDisplayType: PropTypes.string,
};

Preview.defaultProps = {
  onDisplayTypeChange: () => {},
  selectedDisplayType: 'desktop',
};
Preview.displayName = 'FormEditorPreview';
export { Preview };
