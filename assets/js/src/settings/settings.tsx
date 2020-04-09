import React from 'react';
import { Switch, Route, Redirect } from 'react-router-dom';
import Notices from 'notices/notices.jsx';
import Loading from 'common/loading';
import { t } from 'common/functions';
import {
  Advanced,
  Basics,
  KeyActivation,
  SendWith,
  SignupConfirmation,
  WooCommerce,
  OtherSendingMethods,
} from './pages';
import Tabs from './components/tabs';
import { useSelector } from './store/hooks';

export default function Settings() {
  const isSaving = useSelector('isSaving')();
  return (
    <>
      {isSaving && <Loading />}
      <Notices />
      <h1 className="title">{t('settings')}</h1>
      <Tabs />
      <Switch>
        <Route path="/basics" component={Basics} />
        <Route path="/signup" component={SignupConfirmation} />
        <Route exact path="/mta" component={SendWith} />
        <Route path="/mta/other" component={OtherSendingMethods} />
        <Route path="/woocommerce" component={WooCommerce} />
        <Route path="/advanced" component={Advanced} />
        <Route path="/premium" component={KeyActivation} />
        <Redirect to="/basics" />
      </Switch>
    </>
  );
}
