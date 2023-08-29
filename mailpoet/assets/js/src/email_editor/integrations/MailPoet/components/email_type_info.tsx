import { createPortal, useRef, useEffect } from '@wordpress/element';
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
    <div>
      <h2>Here comes email type info</h2>
    </div>,
    emailInfoPortalEl.current,
  );
}
