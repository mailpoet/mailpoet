import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { SendButtonSlot } from './components/send_button_slot';

function Editor() {
  return (
    <SendButtonSlot>
      {/* eslint-disable-next-line no-console */}
      <Button variant="primary" onClick={() => console.log('Send Clicked')}>
        {__('Send', 'mailpoet')}
      </Button>
    </SendButtonSlot>
  );
}

function initializeEditor() {
  registerPlugin('mailpoet-email-editor', {
    render: Editor,
  });
}

export { initializeEditor };
