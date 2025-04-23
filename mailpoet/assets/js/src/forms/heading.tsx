import { useState } from 'react';
import { MailPoet } from 'mailpoet';
import { __ } from '@wordpress/i18n';
import { PageHeader } from 'common/page-header';
import { CompensateScreenOptions } from 'common/compensate-screen-options/compensate-screen-options';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';

export const onAddNewForm = (): void => {
  MailPoet.trackEvent('Forms > Add New');
  setTimeout(() => {
    window.location.href = window.mailpoet_form_template_selection_url;
  }, 200); // leave some time for the event to track
};

function FormsHeading(): JSX.Element {
  const [loading, setLoading] = useState<boolean>(false);
  return (
    <>
      <CompensateScreenOptions />
      <TopBarWithBoundary />
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
          {__('Add new form', 'mailpoet')}
        </button>
      </PageHeader>
    </>
  );
}

FormsHeading.displayName = 'FormsHeading';

export { FormsHeading };
