import { useEffect, useState } from 'react';
import { Button, Modal, Spinner } from '@wordpress/components';
import { InputControl, Stack } from '@wordpress/ui';
import { __ } from '@wordpress/i18n';
import type { EmailPreset } from 'common/email-presets';
import { loadPreview } from './thumbnail';

type NameFormProps = {
  defaultTitle: string;
  submitLabel: string;
  // Resolves to true when the preset was saved.
  onSubmit: (title: string) => Promise<boolean>;
  onClose: () => void;
};

export function PresetNameForm({
  defaultTitle,
  submitLabel,
  onSubmit,
  onClose,
}: NameFormProps) {
  const [title, setTitle] = useState(defaultTitle);
  const [isSaving, setIsSaving] = useState(false);
  const trimmedTitle = title.trim();

  const submit = async () => {
    setIsSaving(true);
    const isSaved = await onSubmit(trimmedTitle);
    setIsSaving(false);
    if (isSaved) {
      onClose();
    }
  };

  return (
    <form
      onSubmit={(event) => {
        event.preventDefault();
        if (trimmedTitle !== '' && !isSaving) void submit();
      }}
    >
      <Stack direction="column" gap="lg">
        <InputControl
          label={__('Preset name', 'mailpoet')}
          value={title}
          onValueChange={setTitle}
        />
        <Stack direction="row" gap="sm" justify="flex-end">
          <Button
            __next40pxDefaultSize
            variant="tertiary"
            onClick={onClose}
            disabled={isSaving}
          >
            {__('Cancel', 'mailpoet')}
          </Button>
          <Button
            __next40pxDefaultSize
            variant="primary"
            type="submit"
            isBusy={isSaving}
            disabled={isSaving || trimmedTitle === ''}
          >
            {submitLabel}
          </Button>
        </Stack>
      </Stack>
    </form>
  );
}

type PreviewModalProps = {
  preset: EmailPreset;
  onClose: () => void;
};

export function PresetPreviewModal({ preset, onClose }: PreviewModalProps) {
  const [html, setHtml] = useState<string | null>(null);
  const [hasError, setHasError] = useState(false);

  useEffect(() => {
    const controller = new AbortController();
    loadPreview(
      preset.id,
      `${preset.id}:${preset.updated_at}`,
      controller.signal,
    )
      .then(setHtml)
      .catch((error: Error) => {
        if (error.name !== 'AbortError') setHasError(true);
      });
    return () => controller.abort();
  }, [preset.id, preset.updated_at]);

  return (
    <Modal
      title={preset.title}
      onRequestClose={onClose}
      size="large"
      className="mailpoet-email-templates-preview-modal"
    >
      {html === null && !hasError && <Spinner />}
      {hasError && <p>{__('Preview unavailable', 'mailpoet')}</p>}
      {html !== null && (
        <iframe title={preset.title} sandbox="" srcDoc={html} />
      )}
    </Modal>
  );
}
