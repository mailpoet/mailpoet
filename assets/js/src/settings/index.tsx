import React from 'react';
import ReactDOM from 'react-dom';

const App = () => (
  <>
    <h1 className="title">Settings</h1>
    <pre><code>{JSON.stringify((window as any).mailpoet_settings)}</code></pre>
  </>
);

const container = document.getElementById('settings_container');
if (container) {
  ReactDOM.render(<App />, container);
}
