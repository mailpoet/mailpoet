import React from 'react';
import { SaveButton } from 'settings/components';
import { t } from 'settings/utils';
import DefaultSender from './default_sender';
import SubscribeOn from './subscribe_on';

export default function Basics() {
  return (
    <div className="mailpoet-settings-grid">
      <DefaultSender />
      <SubscribeOn
        event="on_comment"
        title={t`subscribeInCommentsTitle`}
        description={t`subscribeInCommentsDescription`}
      />
      <SubscribeOn
        event="on_register"
        title={t`subscribeInRegistrationTitle`}
        description={t`subscribeInRegistrationDescription`}
      />
      <SaveButton />
    </div>
  );
}
