import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import { createPortal, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Icon, megaphone } from '@wordpress/icons';
import { store as interfaceStore } from '@wordpress/interface';
import { useSelect } from '@wordpress/data';

// Hacky way to render info about email type above the settings panels
// We watch if the correct panel is active and then render the info above using a portal and dom manipulation
export function EmailTypeInfo() {
  const emailInfoPortalEl = useRef(document.createElement('div'));
  const { activeSidebar } = useSelect((select) => ({
    activeSidebar:
      select(interfaceStore).getActiveComplementaryArea('core/edit-post'),
  }));

  useEffect(() => {
    if (activeSidebar !== 'edit-post/document') return;
    // Locate DOM element for placing the portal for email info and place portal element there
    setTimeout(() => {
      const panelsWrap = document.querySelector('.edit-post-sidebar');
      const editPostStatus = document.querySelector(
        '.edit-post-sidebar .components-panel',
      );
      if (!panelsWrap || !editPostStatus) return;
      panelsWrap.insertBefore(emailInfoPortalEl.current, editPostStatus);
    });
  }, [activeSidebar]);

  if (activeSidebar !== 'edit-post/document') {
    return null;
  }

  return createPortal(
    <Panel className="mailpoet-email-sidebar__email-type-info">
      <PanelBody>
        <PanelRow>
          <span className="email-type-info__icon">
            <Icon icon={megaphone} />
          </span>
          <div className="email-type-info__content">
            <h2>{__('Newsletter', 'mailpoet')}</h2>
            <span>
              {__(
                'Send or schedule a newsletter to connect with your subscribers.',
                'mailpoet',
              )}
            </span>
          </div>
        </PanelRow>
      </PanelBody>
    </Panel>,
    emailInfoPortalEl.current,
  );
}
