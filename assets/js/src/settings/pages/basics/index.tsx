import React from 'react';
import { SaveButton } from 'settings/components';
import { t } from 'settings/utils';
import DefaultSender from './default_sender';
import SubscribeOn from './subscribe_on';
import ManageSubscription from './manage_subscription';
import UnsubscribePage from './unsubscribe_page';
import StatsNotifications from './stats_notifications';
import NewSubscriberNotifications from './new_subscriber_notifications';

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
      <ManageSubscription />
      <UnsubscribePage />
      <StatsNotifications />
      <NewSubscriberNotifications />
      <SaveButton />
    </div>
  );
}
