import { useEffect, useRef, createPortal } from '@wordpress/element';

export function EmailName() {
  const namePortalEl = useRef(document.createElement('div'));
  useEffect(() => {
    // Locate DOM element for placing the portal for email info and place portal element there
    setTimeout(() => {
      const portalWrap = document.getElementsByClassName(
        'edit-post-header__center',
      )[0];
      if (!portalWrap) return;
      portalWrap.append(namePortalEl.current);
    });
  }, []);

  return createPortal(<p>Here comes email name</p>, namePortalEl.current);
}
