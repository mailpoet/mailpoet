import { useState } from 'react';
import { __, _x } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { MobileIcon } from './mobile-icon';
import { DesktopIcon } from './desktop-icon';

function Preview({
  children,
  onDisplayTypeChange = (type) => type,
  selectedDisplayType = 'desktop',
}) {
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
          title={_x('Desktop', 'Desktop browser preview mode', 'mailpoet')}
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
          title={_x('Mobile', 'Mobile browser preview mode', 'mailpoet')}
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
          {__(
            'Psssst. Forms on mobile appear smaller automatically because it’s better for SEO.',
            'mailpoet',
          )}
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

Preview.displayName = 'FormEditorPreview';
export { Preview };
