import ReactDOM from 'react-dom';

function Editor(): JSX.Element {
  return <div>Automation editor</div>;
}

window.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('mailpoet_automation_editor');
  if (root) {
    ReactDOM.render(<Editor />, root);
  }
});
