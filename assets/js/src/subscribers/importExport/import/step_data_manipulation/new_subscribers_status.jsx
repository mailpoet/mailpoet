import React from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

function NewSubscribersStatus({ newSubscribersStatus, setNewSubscribersStatus }) {
  function handleChange(event) {
    setNewSubscribersStatus(event.target.value);
  }

  return (
    <div className="mailpoet_import_select_segment">
      <div className="mailpoet_label_description">{MailPoet.I18n.t('newSubscribersStatus')}</div>
      <label htmlFor="new_subscribers_status">
        <select
          id="new_subscribers_status"
          data-placeholder={MailPoet.I18n.t('select')}
          name="new_subscribers_status"
          onChange={handleChange}
          value={newSubscribersStatus}
        >
          <option value="subscribed">{MailPoet.I18n.t('subscribed')}</option>
          <option value="inactive">{MailPoet.I18n.t('inactive')}</option>
          <option value="unsubscribed">{MailPoet.I18n.t('unsubscribed')}</option>
        </select>
      </label>
    </div>
  );
}

NewSubscribersStatus.propTypes = {
  newSubscribersStatus: PropTypes.string.isRequired,
  setNewSubscribersStatus: PropTypes.func.isRequired,
};

export default NewSubscribersStatus;
