import { useState } from 'react';
import { MailPoet } from 'mailpoet';
import { __ } from '@wordpress/i18n';
import { PageHeader } from 'common/page-header';

export const onAddNewForm = () => {
  MailPoet.trackEvent('Forms > Add New');
  setTimeout(() => {
    window.location = window.mailpoet_form_template_selection_url;
  }, 200); // leave some time for the event to track
};

function FormsHeading() {
  const [loading, setLoading] = useState(false);
  return (
    <PageHeader heading={__('Forms', 'mailpoet')}>
      <button
        onClick={() => {
          setLoading(true);
          onAddNewForm();
        }}
        data-automation-id="create_new_form"
        className={`page-title-action ${
          loading ? 'mailpoet-button-with-spinner' : ''
        }`}
        type="button"
      >
        {__('Add New Form', 'mailpoet')}
      </button>
    </PageHeader>
  );
}

FormsHeading.displayName = 'FormsHeading';

export { FormsHeading };
