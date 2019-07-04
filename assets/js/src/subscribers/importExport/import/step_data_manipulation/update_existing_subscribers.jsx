import React from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

function UpdateExistingSubscribers({ updateExistingSubscribers, setUpdateExistingSubscribers }) {
  return (
    <div className="mailpoet_update_existing_subscribers">
      <div className="mailpoet_label_description">{MailPoet.I18n.t('updateExistingSubscribers')}</div>
      <label htmlFor="update_existing_subscribers">
        <input
          id="update_existing_subscribers"
          type="radio"
          name="update_existing_subscribers"
          checked={updateExistingSubscribers}
          onChange={() => setUpdateExistingSubscribers(true)}
        />
        {MailPoet.I18n.t('updateExistingSubscribersYes')}
      </label>
      <label htmlFor="dont_update_existing_subscribers">
        <input
          id="dont_update_existing_subscribers"
          type="radio"
          name="update_existing_subscribers"
          checked={!updateExistingSubscribers}
          onChange={() => setUpdateExistingSubscribers(false)}
        />
        {MailPoet.I18n.t('updateExistingSubscribersNo')}
      </label>
    </div>
  );
}

UpdateExistingSubscribers.propTypes = {
  setUpdateExistingSubscribers: PropTypes.func.isRequired,
  updateExistingSubscribers: PropTypes.bool.isRequired,
};

export default UpdateExistingSubscribers;
