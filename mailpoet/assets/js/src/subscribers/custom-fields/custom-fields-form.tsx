import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import {
  Button,
  Flex,
  FlexItem,
  Modal,
  __experimentalSpacer as Spacer,
} from '@wordpress/components';
import type {
  ApiErrorResponse,
  CustomField,
  CustomFieldDateSettings,
} from './types';
import { createCustomField } from './api';
import {
  buildCustomFieldPayload,
  CustomFieldFormFields,
  getInitialCustomFieldFormData,
  validateCustomFieldFormData,
} from './custom-field-form-fields';

type Props = {
  dateSettings: CustomFieldDateSettings;
  onClose: () => void;
  onSuccess: (customField: CustomField) => void;
};

export function CustomFieldsForm({
  dateSettings,
  onClose,
  onSuccess,
}: Props): JSX.Element {
  const [data, setData] = useState(() =>
    getInitialCustomFieldFormData(dateSettings),
  );
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    const validationError = validateCustomFieldFormData(data);
    if (validationError) {
      setError(validationError);
      return;
    }

    setIsSaving(true);
    setError(null);
    try {
      onSuccess(await createCustomField(buildCustomFieldPayload(data)));
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
      title={__('Add new custom field', 'mailpoet')}
      onRequestClose={onClose}
      className="mailpoet-custom-fields-form-modal"
      focusOnMount
    >
      <form onSubmit={handleSubmit}>
        <CustomFieldFormFields
          data={data}
          dateSettings={dateSettings}
          disabled={isSaving}
          onChange={setData}
        />

        {error && (
          <>
            <Spacer marginTop={4} />
            <div className="mailpoet-custom-fields-form-error" role="alert">
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
              {__('Create custom field', 'mailpoet')}
            </Button>
          </FlexItem>
        </Flex>
      </form>
    </Modal>
  );
}
