import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';
import Button from 'common/button/button';
import plusIcon from 'common/button/icon/plus';

export const onAddNewForm = () => {
  MailPoet.trackEvent('Forms > Add New', {
    'MailPoet Free version': MailPoet.version,
  });
  setTimeout(() => {
    window.location = window.mailpoet_form_template_selection_url;
  }, 200); // leave some time for the event to track
};

export const FormsHeading = () => {
  const [loading, setLoading] = useState(false);
  return (
    <TopBarWithBeamer>
      <Button
        dimension="small"
        onClick={() => {
          setLoading(true);
          onAddNewForm();
        }}
        withSpinner={loading}
        automationId="create_new_form"
        iconStart={plusIcon}
      >
        {MailPoet.I18n.t('new')}
      </Button>
    </TopBarWithBeamer>
  );
};
