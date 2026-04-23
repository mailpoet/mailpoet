import { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';
import {
  Modal,
  Button,
  Flex,
  FlexItem,
  __experimentalSpacer as Spacer,
} from '@wordpress/components';
import { DataForm } from '@wordpress/dataviews';
import { formFields } from './fields';
import type { Tag, ApiErrorResponse } from './types';
import { createTag, updateTag } from './api';

type FormData = Pick<Tag, 'name' | 'description'>;

type Props = {
  initialTag?: Tag;
  onClose: () => void;
  onSuccess: (tag: Tag) => void;
};

export function TagsForm({ initialTag, onClose, onSuccess }: Props) {
  const isEdit = !!initialTag;
  const [data, setData] = useState<FormData>(() => ({
    name: initialTag?.name ?? '',
    description: initialTag?.description ?? '',
  }));
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    // Modal focuses its close button first; move focus to the name field.
    requestAnimationFrame(() => {
      const nameInput = document.querySelector<HTMLInputElement>(
        '.mailpoet-tags-form-modal input',
      );
      nameInput?.focus();
    });
  }, []);

  const title = isEdit
    ? __('Edit tag', 'mailpoet')
    : __('Add new tag', 'mailpoet');

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    if (!data.name.trim()) {
      setError(__('Tag name is required.', 'mailpoet'));
      return;
    }
    setIsSaving(true);
    setError(null);
    try {
      const payload = {
        name: data.name.trim(),
        description: data.description,
      };
      const saved = initialTag
        ? await updateTag(initialTag.id, payload)
        : await createTag(payload);
      onSuccess(saved);
    } catch (err) {
      const apiError = err as ApiErrorResponse;
      setError(
        apiError?.message ||
          __('Something went wrong. Please try again.', 'mailpoet'),
      );
    } finally {
      setIsSaving(false);
    }
  }

  return (
    <Modal
      title={title}
      onRequestClose={onClose}
      className="mailpoet-tags-form-modal"
      focusOnMount
    >
      <form onSubmit={handleSubmit}>
        <DataForm<FormData>
          data={data}
          fields={formFields as never}
          form={{
            fields: ['name', 'description'],
          }}
          onChange={(edits) => setData((current) => ({ ...current, ...edits }))}
        />
        {error && (
          <>
            <Spacer marginTop={4} />
            <div className="mailpoet-tags-form-error" role="alert">
              {error}
            </div>
          </>
        )}
        <Spacer marginTop={6} />
        <Flex justify="flex-end" gap={3}>
          <FlexItem>
            <Button
              variant="tertiary"
              onClick={onClose}
              disabled={isSaving}
              __next40pxDefaultSize
            >
              {__('Cancel', 'mailpoet')}
            </Button>
          </FlexItem>
          <FlexItem>
            <Button
              variant="primary"
              type="submit"
              isBusy={isSaving}
              disabled={isSaving}
              __next40pxDefaultSize
            >
              {isEdit ? __('Save', 'mailpoet') : __('Create tag', 'mailpoet')}
            </Button>
          </FlexItem>
        </Flex>
      </form>
    </Modal>
  );
}
