import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { Toggle } from 'common/form/toggle/toggle';
import type { FormListingItem } from './api';

type Props = {
  form: FormListingItem;
};

export function FormStatusToggle({ form }: Props): JSX.Element {
  const [enabled, setEnabled] = useState(form.status === 'enabled');
  const [submitting, setSubmitting] = useState(false);

  const handleChange = (
    checked: boolean,
    event: React.ChangeEvent<HTMLInputElement>,
  ): void => {
    event.persist();
    const previous = enabled;
    setEnabled(checked);
    setSubmitting(true);

    void MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'forms',
      action: 'setStatus',
      data: {
        id: Number(form.id),
        status: checked ? 'enabled' : 'disabled',
      },
    })
      .done((response: { data: { status: string } }) => {
        if (response.data.status === 'enabled') {
          MailPoet.Notice.success(
            __('Your Form is now activated!', 'mailpoet'),
          );
        }
      })
      .fail((response: { errors: Array<unknown> }) => {
        MailPoet.Notice.showApiErrorNotice(response);
        setEnabled(previous);
      })
      .always(() => setSubmitting(false));
  };

  return (
    <div>
      <Toggle
        onCheck={handleChange}
        data-id={form.id}
        dimension="small"
        checked={enabled}
        disabled={submitting}
      />
      <p>
        {__('Sign-ups', 'mailpoet')}
        {': '}
        {form.signups.toLocaleString()}
      </p>
    </div>
  );
}

FormStatusToggle.displayName = 'FormStatusToggle';
