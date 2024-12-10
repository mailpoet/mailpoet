import { useState } from 'react';
import { MailPoet } from 'mailpoet';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';
import { Button } from 'common/button/button';
import { plusIcon } from 'common/button/icon/plus';

export const onAddNewForm = () => {
  MailPoet.trackEvent('Forms > Add New');
  setTimeout(() => {
    window.location = window.mailpoet_form_template_selection_url;
  }, 200); // leave some time for the event to track
};

function FormsHeading() {
  const [loading, setLoading] = useState(false);
  return (
    <TopBarWithBoundary>
      <Button
        onClick={() => {
          setLoading(true);
          onAddNewForm();
        }}
        withSpinner={loading}
        automationId="create_new_form"
        variant="secondary"
        iconStart={plusIcon}
      >
        {MailPoet.I18n.t('new')}
      </Button>
    </TopBarWithBoundary>
  );
}

FormsHeading.displayName = 'FormsHeading';

export { FormsHeading };
