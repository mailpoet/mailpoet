import React from 'react';
import ReactDOM from 'react-dom';

function App(): JSX.Element {
  return (
    <div>Hello from React.</div>
  );
}

window.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('mailpoet_automation');
  if (root) {
    ReactDOM.render(<App />, root);
  }
});
