import { registerPlugin } from '@wordpress/plugins';
import { useSelect, select as directSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { NextButtonSlot } from './core/components/next_button_slot';
import { NextButton } from './integration/components/next_button';
import { MailPoetEmailData } from './types';

import './email_editor.scss';

// Hack to temporarily disable block patterns
directSelect(coreStore).getBlockPatterns = () => [];
directSelect(coreStore).getBlockPatternCategories = () => [];

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
    <NextButtonSlot>
      <NextButton newsletterId={mailpoetData?.id ?? null} />
    </NextButtonSlot>
  );
}

function initializeEditor() {
  registerPlugin('mailpoet-email-editor', {
    render: Editor,
  });
}

export { initializeEditor };
