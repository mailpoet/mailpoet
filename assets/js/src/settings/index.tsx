import React from 'react';
import ReactDOM from 'react-dom';
import {
  HashRouter, Switch, Route, Redirect,
} from 'react-router-dom';
import Notices from 'notices/notices.jsx';
import { GlobalContext, useGlobalContextValue } from 'context';
import MailPoet from 'mailpoet';
import {
  Advanced,
  Basics,
  KeyActivation,
  SendWith,
  SignupConfirmation,
  WooCommerce,
} from './pages';
import { initStore } from './store';
import Tabs from './components/tabs';

const App = () => (
  <GlobalContext.Provider value={useGlobalContextValue(window)}>
    <HashRouter>
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
    </HashRouter>
  </GlobalContext.Provider>
);

const container = document.getElementById('settings_container');
if (container) {
  initStore(window);
  ReactDOM.render(<App />, container);
}
