import React from 'react';
import ReactDOM from 'react-dom';
import { initStore } from './store';

const App = () => null;

const container = document.getElementById('settings_container');
if (container) {
  initStore((window as any).mailpoet_settings);
  ReactDOM.render(<App />, container);
}
