import { FormEvent, useState } from 'react';
import { __ } from '@wordpress/i18n';
import {
  Button,
  Flex,
  FlexItem,
  Modal,
  __experimentalSpacer as Spacer,
} from '@wordpress/components';
import { MailPoet } from 'mailpoet';
import { createCustomField } from '../../../custom-fields/api';
import type {
  ApiErrorResponse,
  CustomField,
  CustomFieldDateSettings,
} from '../../../custom-fields/types';
import {
  buildCustomFieldPayload,
  CustomFieldFormFields,
  getInitialCustomFieldFormData,
  validateCustomFieldFormData,
} from '../../../custom-fields/custom-field-form-fields';

type Props = {
  dateSettings: CustomFieldDateSettings;
  onClose: () => void;
  onSuccess: (customField: CustomField) => void;
};

function getApiErrorMessage(error: ApiErrorResponse): string {
  return (
    error?.message ||
    MailPoet.I18n.t('customFieldCreateError') ||
    __('Custom field could not be created', 'mailpoet')
  );
}

export function CreateCustomFieldModal({
  dateSettings,
  onClose,
  onSuccess,
}: Props): JSX.Element {
  const [data, setData] = useState(() =>
    getInitialCustomFieldFormData(dateSettings),
  );
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
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
      setError(getApiErrorMessage(err as ApiErrorResponse));
    } finally {
      setIsSaving(false);
    }
  }

  return (
    <Modal
      title={MailPoet.I18n.t('addNewField')}
      onRequestClose={onClose}
      className="mailpoet-custom-fields-form-modal"
      focusOnMount
    >
      <form
        onSubmit={handleSubmit}
        data-automation-id="create_custom_field_form"
      >
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
