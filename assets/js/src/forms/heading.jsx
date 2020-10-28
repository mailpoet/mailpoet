import React from 'react';
import { Link } from 'react-router-dom';
import MailPoet from 'mailpoet';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';
import plusIcon from 'common/button/icon/plus';

export const onAddNewForm = () => {
  MailPoet.trackEvent('Forms > Add New', {
    'MailPoet Free version': MailPoet.version,
  });
  setTimeout(() => {
    window.location = window.mailpoet_form_template_selection_url;
  }, 200); // leave some time for the event to track
};

export const FormsHeading = () => (
  <TopBarWithBeamer>
    <Link
      className="mailpoet-button mailpoet-button-small"
      to="/new"
      onClick={onAddNewForm}
      data-automation-id="create_new_form"
    >
      {plusIcon}
      <span>{MailPoet.I18n.t('new')}</span>
    </Link>
  </TopBarWithBeamer>
);
