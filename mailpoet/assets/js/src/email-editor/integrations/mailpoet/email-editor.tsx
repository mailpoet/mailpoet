import { registerPlugin } from '@wordpress/plugins';
import { useSelect, select as directSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { NextButtonSlot } from 'email_editor/engine/components/next_button_slot';
import { LayoutStyles } from 'email_editor/engine/components/layout_styles';
import { useDisableWelcomeGuide } from 'email_editor/engine/hooks';
import { NextButton } from './components/next_button';
import { SettingsSidebar } from './components/settings_panel';
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

  // We don't want to show the editor welcome guide as it is not relevant to emails
  useDisableWelcomeGuide();

  return (
    <>
      <LayoutStyles />
      <NextButtonSlot>
        <NextButton newsletterId={mailpoetData?.id ?? null} />
      </NextButtonSlot>
      <SettingsSidebar />
    </>
  );
}

function initializeEditor() {
  registerPlugin('mailpoet-email-editor', {
    render: Editor,
  });
}

export { initializeEditor };
