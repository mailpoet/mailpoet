import React from 'react';
import SendingMethod from './sending_method';
import SPF from './spf';

export default function OtherSendingMethods() {
  return (
    <div className="mailpoet-settings-grid">
      <SendingMethod />

      <SPF />
    </div>
  );
}
