import React from 'react';
import { useLocation } from 'react-router-dom';
import MailPoet from 'mailpoet';
import { useSelector } from 'settings/store/hooks';
import TabLink from './tab_link';

export default () => {
  const { pathname } = useLocation();
  const [, current] = pathname.split('/');
  const hasWooCommerce = useSelector('hasWooCommerce')();

  return (
    <h2 className="nav-tab-wrapper">
      <TabLink
        name="basics"
        current={current}
        automationId="basic_settings_tab"
      >
        {MailPoet.I18n.t('basicsTab')}
      </TabLink>
      <TabLink
        name="signup"
        current={current}
        automationId="signup_settings_tab"
      >
        {MailPoet.I18n.t('signupConfirmationTab')}
      </TabLink>
      <TabLink
        name="mta"
        current={current}
        automationId="send_with_settings_tab"
      >
        {MailPoet.I18n.t('sendWithTab')}
      </TabLink>
      {hasWooCommerce && (
      <TabLink
        name="woocommerce"
        current={current}
        automationId="woocommerce_settings_tab"
      >
        {MailPoet.I18n.t('wooCommerceTab')}
      </TabLink>
      )}
      <TabLink
        name="advanced"
        current={current}
        automationId="settings-advanced-tab"
      >
        {MailPoet.I18n.t('advancedTab')}
      </TabLink>
      <TabLink
        name="premium"
        current={current}
        automationId="activation_settings_tab"
      >
        {MailPoet.I18n.t('keyActivationTab')}
      </TabLink>
    </h2>
  );
};
