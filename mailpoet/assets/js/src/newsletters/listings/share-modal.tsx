import { __, sprintf } from '@wordpress/i18n';
import { Button, Modal, Notice, TextControl } from '@wordpress/components';
import { caution, check, copy, share } from '@wordpress/icons';
import { useState } from 'react';
import { NewsLetter } from 'common/newsletter';
import { copyToClipboard } from 'utils';
import { getShareLinks, ShareLink } from './share-links';

export const SHARE_VISIBILITY_PUBLIC = 'public';

type Props = {
  newsletter: NewsLetter;
  onClose: () => void;
  onMakePublic: (newsletter: NewsLetter) => void;
};

const shareLinkLabels: Record<ShareLink['name'], string> = {
  facebook: __('Facebook', 'mailpoet'),
  x: __('X', 'mailpoet'),
  whatsapp: __('WhatsApp', 'mailpoet'),
  email: __('Email', 'mailpoet'),
};

const copyButtonLabels = {
  idle: __('Copy to clipboard', 'mailpoet'),
  copied: __('Copied to clipboard', 'mailpoet'),
  failed: __('Could not copy to clipboard', 'mailpoet'),
};

const copyButtonIcons = {
  idle: copy,
  copied: check,
  failed: caution,
};

function FacebookIcon() {
  return (
    <svg
      aria-hidden="true"
      focusable="false"
      viewBox="0 0 24 24"
      xmlns="http://www.w3.org/2000/svg"
    >
      <path
        fill="currentColor"
        d="M13.5 21v-7.2h2.4l.5-3h-2.9V8.9c0-.8.4-1.6 1.7-1.6h1.3V4.7c-.2 0-1.2-.2-2.3-.2-2.4 0-4 1.5-4 4.1v2.2H7.5v3h2.7V21h3.3Z"
      />
    </svg>
  );
}

function XIcon() {
  return (
    <svg
      aria-hidden="true"
      focusable="false"
      viewBox="0 0 24 24"
      xmlns="http://www.w3.org/2000/svg"
    >
      <path
        fill="currentColor"
        d="M14.2 10.4 21 3h-1.6l-5.9 6.4L8.8 3H3.3l7.1 9.6L3.3 21H5l6.2-7.3 5 7.3h5.5l-7.5-10.6Zm-2.2 2.5-.7-1-5.7-7.6H8l4.6 6.2.7 1 6 8.1h-2.4L12 12.9Z"
      />
    </svg>
  );
}

function WhatsAppIcon() {
  return (
    <svg
      aria-hidden="true"
      focusable="false"
      viewBox="0 0 24 24"
      xmlns="http://www.w3.org/2000/svg"
    >
      <path
        fill="currentColor"
        d="M12 3.5a8.3 8.3 0 0 0-7.2 12.4L4 20.5l4.7-1.2A8.5 8.5 0 1 0 12 3.5Zm0 15.3c-1.1 0-2.2-.3-3.2-.8l-.3-.2-2.7.7.7-2.6-.2-.3A6.8 6.8 0 1 1 12 18.8Zm3.8-5.1c-.2-.1-1.2-.6-1.4-.7-.2 0-.3-.1-.5.1l-.6.8c-.1.2-.3.2-.5.1-.2-.1-1-.4-1.9-1.1-.7-.6-1.1-1.3-1.3-1.5-.1-.2 0-.4.1-.5l.4-.4.2-.4c.1-.1 0-.3 0-.4 0-.1-.5-1.2-.7-1.6-.2-.4-.4-.4-.5-.4h-.5c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 1.9s.8 2.2.9 2.3c.1.2 1.6 2.5 3.9 3.5.5.2 1 .4 1.3.5.5.2 1 .1 1.4.1.4-.1 1.2-.5 1.4-1 .2-.5.2-.9.2-1 0-.1-.2-.2-.4-.3Z"
      />
    </svg>
  );
}

function EmailIcon() {
  return (
    <svg
      aria-hidden="true"
      focusable="false"
      viewBox="0 0 24 24"
      xmlns="http://www.w3.org/2000/svg"
    >
      <path
        fill="currentColor"
        d="M4.8 6h14.4c1 0 1.8.8 1.8 1.8v8.4c0 1-.8 1.8-1.8 1.8H4.8c-1 0-1.8-.8-1.8-1.8V7.8C3 6.8 3.8 6 4.8 6Zm7.2 6.4 7.1-4.9H4.9l7.1 4.9Zm-7.5 3.8c0 .2.1.3.3.3h14.4c.2 0 .3-.1.3-.3V8.9l-7.1 4.9a.8.8 0 0 1-.8 0L4.5 8.9v7.3Z"
      />
    </svg>
  );
}

const shareLinkIcons: Record<ShareLink['name'], JSX.Element> = {
  facebook: <FacebookIcon />,
  x: <XIcon />,
  whatsapp: <WhatsAppIcon />,
  email: <EmailIcon />,
};

export function NewsletterShareModal({
  newsletter,
  onClose,
  onMakePublic,
}: Props) {
  const [copyStatus, setCopyStatus] =
    useState<keyof typeof copyButtonLabels>('idle');
  const shareUrlInputId = `mailpoet-share-url-${newsletter.id}`;
  const title = newsletter.campaign_name || newsletter.subject || '';
  const canShare = newsletter.can_share && newsletter.share_url;
  const isSupported = newsletter.is_share_supported;
  const hasNativeShare =
    typeof navigator !== 'undefined' && typeof navigator.share === 'function';

  const shareNative = async () => {
    if (!navigator.share || !newsletter.share_url) {
      return;
    }
    await navigator.share({
      title,
      url: newsletter.share_url,
    });
  };

  const copyShareUrl = async () => {
    await copyToClipboard(
      shareUrlInputId,
      (wasSuccessful) => {
        if (!wasSuccessful) {
          setCopyStatus('failed');
          return;
        }
        setCopyStatus('copied');
        window.setTimeout(() => setCopyStatus('idle'), 3000);
      },
      true,
    );
  };

  return (
    <Modal
      title={__('Share email', 'mailpoet')}
      onRequestClose={onClose}
      className="mailpoet-share-email-modal"
      size="medium"
    >
      <div
        className="mailpoet-share-email-modal__content"
        data-automation-id="newsletter-share-modal"
      >
        <p className="mailpoet-share-email-modal__intro">
          {sprintf(
            // translators: %s is the newsletter subject or campaign name.
            __('Share "%s" using its public email page.', 'mailpoet'),
            title,
          )}
        </p>
        {canShare ? (
          <>
            <div className="mailpoet-share-email-modal__url-row">
              <TextControl
                id={shareUrlInputId}
                className="mailpoet-share-email-modal__url-control"
                type="url"
                label={__('Public share link', 'mailpoet')}
                readOnly
                value={newsletter.share_url || ''}
                onChange={() => undefined}
                onFocus={(event) => event.target.select()}
                data-automation-id="newsletter-share-url"
                __next40pxDefaultSize
              />
              <Button
                type="button"
                variant="secondary"
                icon={copyButtonIcons[copyStatus]}
                disabled={copyStatus === 'copied'}
                accessibleWhenDisabled
                onClick={() => void copyShareUrl()}
                data-automation-id="newsletter-share-copy"
                __next40pxDefaultSize
              >
                {copyButtonLabels[copyStatus]}
              </Button>
            </div>
            <div className="mailpoet-share-email-modal__actions">
              {hasNativeShare && (
                <Button
                  type="button"
                  variant="secondary"
                  icon={share}
                  onClick={() => void shareNative()}
                  data-automation-id="newsletter-share-native"
                  __next40pxDefaultSize
                >
                  {__('Share', 'mailpoet')}
                </Button>
              )}
              {getShareLinks(newsletter.share_url, title).map((link) => (
                <Button
                  key={link.name}
                  href={link.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className={`mailpoet-share-email-modal__social-button mailpoet-share-email-modal__social-button--${link.name}`}
                  icon={shareLinkIcons[link.name]}
                  data-automation-id={`newsletter-share-${link.name}`}
                  __next40pxDefaultSize
                >
                  {shareLinkLabels[link.name]}
                </Button>
              ))}
            </div>
          </>
        ) : (
          <>
            <Notice
              status="warning"
              isDismissible={false}
              className="mailpoet-share-email-modal__notice"
              data-automation-id="newsletter-share-unavailable"
            >
              {newsletter.share_unavailable_reason ||
                __('This email cannot be shared yet.', 'mailpoet')}
            </Notice>
            {isSupported && (
              <div className="mailpoet-share-email-modal__footer">
                <Button
                  type="button"
                  variant="primary"
                  onClick={() => onMakePublic(newsletter)}
                  data-automation-id="newsletter-share-make-public"
                  __next40pxDefaultSize
                >
                  {__('Make public and share', 'mailpoet')}
                </Button>
              </div>
            )}
          </>
        )}
      </div>
    </Modal>
  );
}
