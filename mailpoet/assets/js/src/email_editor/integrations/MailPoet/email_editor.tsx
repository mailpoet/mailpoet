import { registerPlugin } from '@wordpress/plugins';
import { useSelect, select as directSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { NextButtonSlot } from 'email_editor/engine/components/next_button_slot';
import { useDisableWelcomeGuide } from 'email_editor/engine/hooks';
import { SelectTemplate } from 'email_editor/integrations/MailPoet/components/select_template';
import { StylesSidebar } from 'email_editor/integrations/MailPoet/components/styles_sidebar';
import { EmailSettings } from 'email_editor/integrations/MailPoet/components/email_settings';
import { MpPreviewOptions } from 'email_editor/integrations/MailPoet/components/preview_options';
import { SendPanel } from 'email_editor/integrations/MailPoet/components/publish_panel';

import { NextButton } from './components/next_button';
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
      <NextButtonSlot>
        <>
          <MpPreviewOptions />
          <NextButton newsletterId={mailpoetData?.id ?? null} />
        </>
      </NextButtonSlot>
      <SelectTemplate />
      <StylesSidebar />
      <EmailSettings />
      <SendPanel />
    </>
  );
}

function initializeEditor() {
  registerPlugin('mailpoet-email-editor', {
    render: Editor,
  });
}

export { initializeEditor };
