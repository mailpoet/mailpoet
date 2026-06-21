import { useEffect, useRef, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { storeName } from '../../editor/store';
import { EmailPreviewModal } from '../../components/email-preview-modal';
import { Step as StepData } from '../../editor/components/automation/types';

const SEND_EMAIL_STEP_KEY = 'mailpoet:send-email';

type PreviewableEmail = {
  stepId: string;
  name: string;
  subject: string;
  preheader: string;
  pattern: string;
};

type Props = {
  // Used to reset preview state when the detail modal switches templates.
  templateSlug: string;
};

export function TemplateEmailPreviews({
  templateSlug,
}: Props): JSX.Element | null {
  const requestVersionRef = useRef(0);
  const [previewHtml, setPreviewHtml] = useState<string | null>(null);
  const [loadingStepId, setLoadingStepId] = useState<string | null>(null);
  const [errorStepId, setErrorStepId] = useState<string | null>(null);

  // Close any open preview and clear transient state when the template changes,
  // so a stale modal/error from the previous template is never shown. Bumping
  // the request version also invalidates any in-flight preview request.
  useEffect(() => {
    requestVersionRef.current += 1;
    setPreviewHtml(null);
    setLoadingStepId(null);
    setErrorStepId(null);
  }, [templateSlug]);

  const emails = useSelect((select): PreviewableEmail[] => {
    const store = select(storeName);
    if (!store || typeof store.getAutomationData !== 'function') {
      return [];
    }
    const steps: Record<string, StepData> =
      store.getAutomationData()?.steps ?? {};
    return Object.values(steps)
      .filter(
        (step) =>
          step.key === SEND_EMAIL_STEP_KEY &&
          typeof step.args?.pattern === 'string' &&
          step.args.pattern !== '',
      )
      .map((step) => ({
        stepId: step.id,
        name: String(step.args?.name ?? ''),
        subject: String(step.args?.subject ?? ''),
        preheader: String(step.args?.preheader ?? ''),
        pattern: String(step.args?.pattern ?? ''),
      }));
  }, []);

  if (emails.length === 0) {
    return null;
  }

  const openPreview = async (email: PreviewableEmail): Promise<void> => {
    requestVersionRef.current += 1;
    const requestVersion = requestVersionRef.current;
    setLoadingStepId(email.stepId);
    setErrorStepId(null);
    // Drop any previously shown email so the modal never displays a stale
    // preview while the new request is in flight.
    setPreviewHtml(null);
    try {
      const path = addQueryArgs('/automation-template-email-preview', {
        pattern: email.pattern,
        subject: email.subject,
        preheader: email.preheader,
      });
      const response = await apiFetch<{ data: { html: string } }>({
        path,
        method: 'GET',
      });
      // Ignore responses for a superseded template or preview request.
      if (requestVersion !== requestVersionRef.current) {
        return;
      }
      setPreviewHtml(response.data.html);
    } catch (error) {
      if (requestVersion !== requestVersionRef.current) {
        return;
      }
      setErrorStepId(email.stepId);
    } finally {
      if (requestVersion === requestVersionRef.current) {
        setLoadingStepId(null);
      }
    }
  };

  return (
    <div className="mailpoet-automation-template-email-previews">
      <h3 className="mailpoet-automation-template-email-previews-title">
        {__('Email content', 'mailpoet')}
      </h3>
      <ul className="mailpoet-automation-template-email-previews-list">
        {emails.map((email, index) => (
          <li
            key={email.stepId}
            className="mailpoet-automation-template-email-previews-item"
          >
            <span className="mailpoet-automation-template-email-previews-name">
              {email.name ||
                // translators: %d is the position of the email in the template.
                sprintf(__('Email %d', 'mailpoet'), index + 1)}
            </span>
            <Button
              variant="secondary"
              isBusy={loadingStepId === email.stepId}
              disabled={loadingStepId !== null}
              onClick={() => void openPreview(email)}
            >
              {__('Preview', 'mailpoet')}
            </Button>
            {errorStepId === email.stepId && (
              <span
                className="mailpoet-automation-template-email-previews-error"
                role="alert"
              >
                {__(
                  'Could not load the preview. Please try again.',
                  'mailpoet',
                )}
              </span>
            )}
          </li>
        ))}
      </ul>
      {previewHtml !== null && (
        <EmailPreviewModal
          srcDoc={previewHtml}
          onClose={() => setPreviewHtml(null)}
        />
      )}
    </div>
  );
}
