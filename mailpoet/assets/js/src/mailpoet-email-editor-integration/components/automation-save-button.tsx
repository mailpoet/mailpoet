import { useRef, useEffect, useCallback, useState } from '@wordpress/element';
import { check, cloud, Icon } from '@wordpress/icons';
import { createPortal } from 'react-dom';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * A "Save draft" button for automation emails that appears as a link-style button
 * on the left side of the header toolbar.
 *
 * This is needed because automation emails use 'private' post status,
 * which WordPress treats as published. The standard "Save Draft" button
 * is not shown for published posts, leaving no way to save changes.
 */
export function AutomationSaveButton() {
  const portalRef = useRef<HTMLSpanElement>(document.createElement('span'));
  const [isMounted, setIsMounted] = useState(false);

  const { isDirty, isSaving } = useSelect(
    (select) => ({
      isDirty: select(editorStore).isEditedPostDirty(),
      isSaving: select(editorStore).isSavingPost(),
    }),
    [],
  );

  const { savePost } = useDispatch(editorStore);

  const handleSave = useCallback(async () => {
    await savePost();
  }, [savePost]);

  // Mount the portal element as the first child of the header settings area
  useEffect(() => {
    const portalElement = portalRef.current;
    let observer: MutationObserver | null = null;

    const findAndMount = () => {
      // Find the header settings container (right side of header toolbar)
      const headerSettings = document.querySelector('.editor-header__settings');

      if (headerSettings) {
        headerSettings.insertBefore(portalElement, headerSettings.firstChild);
        setIsMounted(true);
        return true;
      }
      return false;
    };

    // Try to mount immediately
    if (!findAndMount()) {
      // If not found, wait for the editor to fully load
      observer = new MutationObserver(() => {
        if (findAndMount()) {
          observer?.disconnect();
        }
      });

      observer.observe(document.body, {
        childList: true,
        subtree: true,
      });
    }

    return () => {
      observer?.disconnect();
      portalElement.remove();
      setIsMounted(false);
    };
  }, []);

  // Don't render if not mounted
  if (!isMounted) {
    return null;
  }

  // Show different states based on dirty/saving status
  let buttonLabel: string;
  if (isSaving) {
    buttonLabel = __('Saving', 'mailpoet');
  } else if (isDirty) {
    buttonLabel = __('Save draft', 'mailpoet');
  } else {
    buttonLabel = __('Saved', 'mailpoet');
  }

  return createPortal(
    <Button
      variant="tertiary"
      onClick={handleSave}
      disabled={isSaving || !isDirty}
      className="editor-post-saved-state"
      data-automation-id="email_editor_save_draft_button"
    >
      {isSaving && <Icon icon={cloud} />}
      {!isSaving && !isDirty && <Icon icon={check} />}
      {buttonLabel}
    </Button>,
    portalRef.current,
  );
}
