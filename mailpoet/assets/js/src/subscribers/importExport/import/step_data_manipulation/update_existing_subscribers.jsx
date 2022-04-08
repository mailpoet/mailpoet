import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import Radio from 'common/form/radio/radio';

function UpdateExistingSubscribers({
  updateExistingSubscribers,
  setUpdateExistingSubscribers,
}) {
  return (
    <>
      <div className="mailpoet-settings-label">
        {MailPoet.I18n.t('updateExistingSubscribers')}
      </div>
      <div className="mailpoet-settings-inputs">
        <Radio
          id="update_existing_subscribers"
          name="update_existing_subscribers"
          value="1"
          checked={updateExistingSubscribers}
          onCheck={() => setUpdateExistingSubscribers(true)}
        />
        <label htmlFor="update_existing_subscribers">
          {MailPoet.I18n.t('updateExistingSubscribersYes')}
        </label>
        <span className="mailpoet-gap" />
        <Radio
          id="dont_update_existing_subscribers"
          name="update_existing_subscribers"
          value=""
          checked={!updateExistingSubscribers}
          onCheck={() => setUpdateExistingSubscribers(false)}
        />
        <label htmlFor="dont_update_existing_subscribers">
          {MailPoet.I18n.t('updateExistingSubscribersNo')}
        </label>
      </div>
    </>
  );
}

UpdateExistingSubscribers.propTypes = {
  setUpdateExistingSubscribers: PropTypes.func.isRequired,
  updateExistingSubscribers: PropTypes.bool.isRequired,
};

export default UpdateExistingSubscribers;
