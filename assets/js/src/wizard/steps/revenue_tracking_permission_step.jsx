import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

function RevenueTrackingPermissionStep({ submitForm, loading }) {
  const [allowed, setAllowed] = useState('true');

  const submit = (event) => {
    event.preventDefault();
    if (allowed === undefined) return false;
    submitForm(allowed === 'true');
    return false;
  };

  return (
    <div
      className="
        mailpoet_welcome_wizard_step_content
        mailpoet_welcome_wizard_step_revenue_tracking
        mailpoet_welcome_wizard_centered_column
      "
    >
      <p>{MailPoet.I18n.t('revenueTrackingInfo1')}</p>
      <p>{MailPoet.I18n.t('revenueTrackingInfo2')}</p>
      <form onSubmit={submit} className="mailpoet_wizard_woocommerce_list">
        <label htmlFor="tracking_allowed">
          <input
            id="tracking_allowed"
            type="radio"
            name="import_type"
            checked={allowed === 'true'}
            onChange={e => setAllowed(e.target.value)}
            value="true"
          />
          {MailPoet.I18n.t('revenueTrackingAllow')}
        </label>
        <label htmlFor="tracking_not_allowed">
          <input
            id="tracking_not_allowed"
            type="radio"
            name="import_type"
            checked={allowed === 'false'}
            onChange={e => setAllowed(e.target.value)}
            value="false"
          />
          {MailPoet.I18n.t('revenueTrackingDontAllow')}
        </label>
        <input
          className="button button-primary"
          type="submit"
          value={MailPoet.I18n.t('revenueTrackingSubmit')}
          disabled={loading}
        />
      </form>
    </div>
  );
}

RevenueTrackingPermissionStep.propTypes = {
  submitForm: PropTypes.func.isRequired,
  loading: PropTypes.bool.isRequired,
};

export default RevenueTrackingPermissionStep;
