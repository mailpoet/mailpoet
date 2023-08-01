import { useEffect, useState, createPortal } from '@wordpress/element';

type NextButtonSlotPropType = {
  children: JSX.Element;
};

export function NextButtonSlot({ children }: NextButtonSlotPropType) {
  const [sendButtonPortalEl] = useState(document.createElement('div'));

  // Place element for rendering send button next to publish button
  useEffect(() => {
    const publishButton = document.getElementsByClassName(
      'editor-post-publish-button__button',
    )[0];
    publishButton.parentNode.insertBefore(sendButtonPortalEl, publishButton);
  }, [sendButtonPortalEl]);

  return createPortal(<>{children}</>, sendButtonPortalEl);
}
