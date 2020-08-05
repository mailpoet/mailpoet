import React from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

function ExistingSubscribersStatus({ existingSubscribersStatus, setExistingSubscribersStatus }) {
  function handleChange(event) {
    setExistingSubscribersStatus(event.target.value);
  }

  return (
    <div className="mailpoet_import_select_segment">
      <div className="mailpoet_label_description">{MailPoet.I18n.t('existingSubscribersStatus')}</div>
      <label htmlFor="existing_subscribers_status">
        <select
          id="existing_subscribers_status"
          data-placeholder={MailPoet.I18n.t('select')}
          name="existing_subscribers_status"
          onChange={handleChange}
          value={existingSubscribersStatus}
        >
          <option value="dont_update">{MailPoet.I18n.t('dontUpdate')}</option>
          <option value="subscribed">{MailPoet.I18n.t('subscribed')}</option>
          <option value="inactive">{MailPoet.I18n.t('inactive')}</option>
          <option value="unsubscribed">{MailPoet.I18n.t('unsubscribed')}</option>
        </select>
      </label>
    </div>
  );
}

ExistingSubscribersStatus.propTypes = {
  existingSubscribersStatus: PropTypes.string.isRequired,
  setExistingSubscribersStatus: PropTypes.func.isRequired,
};

export default ExistingSubscribersStatus;
