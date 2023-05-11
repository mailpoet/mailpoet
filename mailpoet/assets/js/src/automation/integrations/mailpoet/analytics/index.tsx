import ReactDOM from 'react-dom';
import { HashRouter } from 'react-router-dom';
import { TopBarWithBeamer } from '../../../../common/top_bar/top_bar';
import { Notices } from '../../../listing/components/notices';
import { Header } from './components/header';
import { Overview } from './components/overview';
import { Tabs } from './components/tabs';
import { createStore } from './store';
import { boot } from './helpers/boot';
import { registerApiErrorHandler } from '../../../listing/api-error-handler';

function Analytics(): JSX.Element {
  return (
    <div className="mailpoet-automation-analytics">
      <Header />
      <Overview />
    </div>
  );
}

function App(): JSX.Element {
  return (
    <HashRouter>
      <TopBarWithBeamer />
      <Notices />
      <Analytics />
      <Tabs />
    </HashRouter>
  );
}

window.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('mailpoet_automation_analytics');
  if (!root) {
    return;
  }
  createStore();
  registerApiErrorHandler();
  boot();
  ReactDOM.render(<App />, root);
});
