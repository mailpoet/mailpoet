import { __ } from '@wordpress/i18n';
import { createRoot } from '@wordpress/element';

function Editor() {
  return <h1>{__('Hello World')}</h1>;
}

export function initialize(elementId: string) {
  const container = document.getElementById(elementId);
  if (!container) {
    return;
  }
  const root = createRoot(container);
  root.render(<Editor />);
}
