import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { InputControl, Stack } from '@wordpress/ui';
import { __ } from '@wordpress/i18n';

type Props = {
  defaultTitle: string;
  // Resolves to true when the preset was saved.
  onSubmit: (title: string) => Promise<boolean>;
  onClose?: () => void;
};

export function SaveAsPresetForm({ defaultTitle, onSubmit, onClose }: Props) {
  const [title, setTitle] = useState(defaultTitle);
  const [isSaving, setIsSaving] = useState(false);
  const trimmedTitle = title.trim();

  const submit = async () => {
    setIsSaving(true);
    const isSaved = await onSubmit(trimmedTitle);
    setIsSaving(false);
    if (isSaved) {
      onClose?.();
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
        <p>
          {__(
            'Save a copy of this email’s content, template, subject and preheader to start new emails from.',
            'mailpoet',
          )}
        </p>
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
            {__('Save preset', 'mailpoet')}
          </Button>
        </Stack>
      </Stack>
    </form>
  );
}
