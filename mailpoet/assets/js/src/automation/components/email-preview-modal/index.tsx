import { Modal, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from 'react';

const EMAIL_PREVIEW_LOAD_TIMEOUT = 15000;

type Props = {
  onClose: () => void;
  title?: string;
  // Provide exactly one source: a URL to load (`src`) or inline HTML (`srcDoc`).
  src?: string;
  srcDoc?: string;
};

export function EmailPreviewModal({
  onClose,
  title,
  src,
  srcDoc,
}: Props): JSX.Element {
  const [iframeLoaded, setIframeLoaded] = useState(false);
  const [hasError, setHasError] = useState(false);
  const modalTitle = title ?? __('Email preview', 'mailpoet');

  // When the source changes, show the loading state again until the iframe
  // reloads, instead of keeping the previous content's loaded/error state.
  useEffect(() => {
    setIframeLoaded(false);
    setHasError(false);
  }, [src, srcDoc]);

  useEffect(() => {
    if (iframeLoaded) {
      return undefined;
    }
    const timeout = window.setTimeout(
      () => setHasError(true),
      EMAIL_PREVIEW_LOAD_TIMEOUT,
    );
    return () => window.clearTimeout(timeout);
  }, [iframeLoaded]);

  return (
    <Modal
      className="mailpoet-automation-email-preview-modal"
      title={modalTitle}
      onRequestClose={onClose}
    >
      <div className="mailpoet-automation-email-preview-modal-content">
        {hasError && (
          <p
            className="mailpoet-automation-email-preview-modal-error"
            role="alert"
          >
            {__(
              'MailPoet could not load the email preview. Please make sure the email still exists and try again.',
              'mailpoet',
            )}
          </p>
        )}
        {!iframeLoaded && !hasError && (
          <div className="mailpoet-automation-email-preview-modal-spinner">
            <Spinner />
          </div>
        )}
        {/* The iframe stays mounted while loading so a slow load is never torn
            down; onLoad clears a timeout-triggered error if it eventually loads. */}
        <iframe
          className="mailpoet-automation-email-preview-modal-iframe"
          data-automation-id="automation_send_email_preview_iframe"
          onLoad={() => {
            setIframeLoaded(true);
            setHasError(false);
          }}
          onError={() => setHasError(true)}
          src={src}
          srcDoc={srcDoc}
          title={modalTitle}
        />
      </div>
    </Modal>
  );
}
