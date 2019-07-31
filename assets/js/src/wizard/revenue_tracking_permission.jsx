import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import RevenueTrackingPermissionStep from './steps/revenue_tracking_permission_step.jsx';

function RevenueTrackingPermission() {
  const [loading, setLoading] = useState(false);

  const handleApiError = (response) => {
    setLoading(false);
    MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
  };

  const updateSettings = (data) => MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'settings',
    action: 'set',
    data,
  }).fail(handleApiError);

  const finishWizard = () => {
    window.location = window.finish_wizard_url;
  };

  const submit = (allowed) => {
    setLoading(true);
    const settings = {
      'woocommerce.accept_cookie_revenue_tracking.enabled': allowed ? 1 : 0,
      'woocommerce.accept_cookie_revenue_tracking.set': 1,
    };
    updateSettings(settings).then(finishWizard);
  };

  return (
    <div className="mailpoet_welcome_wizard_steps mailpoet_welcome_wizard_centered_column">
      <div className="mailpoet_welcome_wizard_header">
        <img src={window.mailpoet_logo_url} width="200" height="87" alt="MailPoet logo" />
      </div>
      <RevenueTrackingPermissionStep loading={loading} submitForm={submit} />
    </div>
  );
}

export default RevenueTrackingPermission;
