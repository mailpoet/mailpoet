import React from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import Select from 'common/form/select/select';

function ExistingSubscribersStatus({ existingSubscribersStatus, setExistingSubscribersStatus }) {
  function handleChange(event) {
    setExistingSubscribersStatus(event.target.value);
  }

  return (
    <>
      <div className="mailpoet-settings-label">
        <label htmlFor="existing_subscribers_status">
          {MailPoet.I18n.t('existingSubscribersStatus')}
        </label>
      </div>
      <div className="mailpoet-settings-inputs">
        <Select
          id="existing_subscribers_status"
          placeholder={MailPoet.I18n.t('select')}
          name="existing_subscribers_status"
          onChange={handleChange}
          defaultValue={existingSubscribersStatus}
        >
          <option value="dont_update">{MailPoet.I18n.t('dontUpdate')}</option>
          <option value="subscribed">{MailPoet.I18n.t('subscribed')}</option>
          <option value="inactive">{MailPoet.I18n.t('inactive')}</option>
          <option value="unsubscribed">{MailPoet.I18n.t('unsubscribed')}</option>
        </Select>
      </div>
    </>
  );
}

ExistingSubscribersStatus.propTypes = {
  existingSubscribersStatus: PropTypes.string.isRequired,
  setExistingSubscribersStatus: PropTypes.func.isRequired,
};

export default ExistingSubscribersStatus;
