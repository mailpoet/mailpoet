import React from 'react';
import ReactDOM from 'react-dom';
import { useSelect, useDispatch } from '@wordpress/data';
import { initStore, STORE_NAME } from './store';

const App = () => {
  const isSaving = useSelect(
    (sel) => sel(STORE_NAME).isSaving(),
    []
  );
  const error = useSelect(
    (sel) => sel(STORE_NAME).getError(),
    []
  );
  const settings = useSelect(
    (sel) => sel(STORE_NAME).getSettings(),
    []
  );
  const email = useSelect(
    (sel) => sel(STORE_NAME).getSetting(['sender', 'address']),
    []
  );
  const actions = useDispatch(STORE_NAME);
  const setEmail = (event) => {
    actions.setSetting(['sender', 'address'], event.target.value);
  };
  const save = () => {
    actions.saveSettings(settings);
  };
  return (
    <>
      <h1 className="title">Settings</h1>
      <p>{JSON.stringify({ email, isSaving, error })}</p>
      <input type="text" value={email} onChange={setEmail} />
      <button type="button" onClick={save}>Save</button>
    </>
  );
};

const container = document.getElementById('settings_container');
if (container) {
  initStore((window as any).mailpoet_settings);
  ReactDOM.render(<App />, container);
}
