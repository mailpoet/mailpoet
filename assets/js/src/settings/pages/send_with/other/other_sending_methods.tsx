import React from 'react';
import SendingMethod from './sending_method';
import SPF from './spf';
import TestSending from './test_sending';
import ActivateOrCancel from './activate_or_cancel';

export default function OtherSendingMethods() {
  return (
    <div className="mailpoet-settings-grid">
      <SendingMethod />

      <SPF />
      <TestSending />
      <ActivateOrCancel />
    </div>
  );
}
