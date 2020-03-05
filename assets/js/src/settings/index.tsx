import React from 'react';
import ReactDOM from 'react-dom';
import { initStore } from './store';
import {
  useSelector, useActions, useSetting, useSettingSetter,
} from './store/hooks';

const App = () => {
  const isSaving = useSelector('isSaving')();
  const error = useSelector('getError')();
  const settings = useSelector('getSettings')();
  const email = useSetting('sender', 'address');
  const setEmail = useSettingSetter('sender', 'address');
  const actions = useActions();
  const onChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    setEmail(event.target.value);
  };
  const save = () => {
    actions.saveSettings(settings);
  };
  return (
    <>
      <h1 className="title">Settings</h1>
      <p>{JSON.stringify({ email, isSaving, error })}</p>
      <input type="text" value={email} onChange={onChange} />
      <button type="button" onClick={save}>Save</button>
    </>
  );
};

const container = document.getElementById('settings_container');
if (container) {
  initStore((window as any).mailpoet_settings);
  ReactDOM.render(<App />, container);
}
