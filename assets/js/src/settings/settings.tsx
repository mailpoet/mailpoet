import React from 'react';
import { Switch, Route, Redirect } from 'react-router-dom';
import Notices from 'notices/notices.jsx';
import MailPoet from 'mailpoet';
import {
  Advanced,
  Basics,
  KeyActivation,
  SendWith,
  SignupConfirmation,
  WooCommerce,
} from './pages';
import Tabs from './components/tabs';

export default function Settings() {
  return (
    <>
      <Notices />
      <h1 className="title">{MailPoet.I18n.t('settings')}</h1>
      <Tabs />
      <Switch>
        <Route path="/basics" component={Basics} />
        <Route path="/signup" component={SignupConfirmation} />
        <Route path="/mta" component={SendWith} />
        <Route path="/woocommerce" component={WooCommerce} />
        <Route path="/advanced" component={Advanced} />
        <Route path="/premium" component={KeyActivation} />
        <Redirect to="/basics" />
      </Switch>
    </>
  );
}
