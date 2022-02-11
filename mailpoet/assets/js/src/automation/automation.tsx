import React from 'react';
import ReactDOM from 'react-dom';
import { useQuery } from './api';

function ApiCheck(): JSX.Element {
  const { data, loading, error } = useQuery('workflows');

  if (!data || loading) {
    return <div>Calling API...</div>;
  }

  return <div>{error ? 'API error!' : 'API OK ✓'}</div>;
}

function App(): JSX.Element {
  return (
    <ApiCheck />
  );
}

window.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('mailpoet_automation');
  if (root) {
    ReactDOM.render(<App />, root);
  }
});
