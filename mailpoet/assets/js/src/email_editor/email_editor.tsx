import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { SendButtonSlot } from './components/send_button_slot';
import { MailPoetEmailData } from './types';

import './email_editor.scss';

function Editor() {
  const { mailpoetData } = useSelect((select) => ({
    mailpoetData:
      (select(editorStore).getEditedPostAttribute(
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        // The getEditedPostAttribute accepts an attribute but typescript thinks it doesn't
        'mailpoet_data',
      ) as MailPoetEmailData) ?? null,
  }));

  return (
    <SendButtonSlot>
      <Button
        variant="primary"
        disabled={!mailpoetData}
        onClick={() => {
          window.location.href = `admin.php?page=mailpoet-newsletters#/send/${mailpoetData.id}`;
        }}
      >
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
