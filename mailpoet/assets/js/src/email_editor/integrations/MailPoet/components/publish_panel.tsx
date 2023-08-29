import { useEffect, useRef, createPortal, useState } from '@wordpress/element';
import { Button } from '@wordpress/components';

export function SendPanel() {
  const [isOpened, setIsOpened] = useState(true);
  const publishPortalEl = useRef(document.createElement('div'));
  useEffect(() => {
    // Locate DOM element for placing the portal for email info and place portal element there
    setTimeout(() => {
      const panelsWrap = document.getElementsByClassName(
        'interface-interface-skeleton__actions',
      )[0];
      if (!panelsWrap) return;
      panelsWrap.append(publishPortalEl.current);
    });
  }, []);

  return (
    isOpened &&
    createPortal(
      <div className="editor-post-publish-panel">
        <div className="editor-post-publish-panel__header">
          <div className="editor-post-publish-panel__header-publish-button">
            <Button onClick={() => null} variant="primary">
              Send
            </Button>
          </div>
          <div className="editor-post-publish-panel__header-cancel-button">
            <Button onClick={() => setIsOpened(false)} variant="secondary">
              Cancel
            </Button>
          </div>
        </div>
        <div className="editor-post-publish-panel__content">
          <p>Panel content</p>
        </div>
        <div className="editor-post-publish-panel__footer">
          <span>Footer content</span>
        </div>
      </div>,
      publishPortalEl.current,
    )
  );
}
