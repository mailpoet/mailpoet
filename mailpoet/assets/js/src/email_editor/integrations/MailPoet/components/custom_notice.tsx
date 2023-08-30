import { useEffect, useRef, createPortal } from '@wordpress/element';
import { NoticeList } from '@wordpress/components';

export function CustomNotice() {
  const noticePortalEl = useRef(document.createElement('div'));
  useEffect(() => {
    // Locate DOM element for placing the portal for email info and place portal element there
    setTimeout(() => {
      const portalWrap = document.getElementsByClassName(
        'interface-interface-skeleton__content',
      )[0];
      if (!portalWrap) return;
      portalWrap.prepend(noticePortalEl.current);
    });
  }, []);

  return createPortal(
    <NoticeList
      className="components-editor-notices__pinned"
      notices={[
        {
          id: 'email-name',
          content: (
            <ul>
              <li>Hello</li>
            </ul>
          ),
        },
      ]}
    />,
    noticePortalEl.current,
  );
}
