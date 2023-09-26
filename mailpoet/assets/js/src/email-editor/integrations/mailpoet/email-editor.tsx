import { useEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { useSelect, select as directSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { HeaderButtonSlot } from 'email-editor/engine/components/header-button-slot';
import { LayoutStyles } from 'email-editor/engine/components/layout-styles';
import { useDisableWelcomeGuide } from 'email-editor/engine/hooks';
import { NextButton } from './components/next-button';
import { SettingsSidebar } from './components/settings-panel';
import { PreviewDropdown } from './components/preview-dropdown';
import { useState } from 'react';
import { createStore } from './store';
import { MailPoetEmailData } from './types';

import './email_editor.scss';

// Hack to temporarily disable block patterns
directSelect(coreStore).getBlockPatterns = () => [];
directSelect(coreStore).getBlockPatternCategories = () => [];

function Editor() {
  const [isStoreInitialized, setIsStoreInitialized] = useState(false);

  const { mailpoetData } = useSelect((select) => ({
    mailpoetData:
      (select(editorStore).getEditedPostAttribute(
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        // The getEditedPostAttribute accepts an attribute but typescript thinks it doesn't
        'mailpoet_data',
      ) as MailPoetEmailData) ?? null,
  }));

  // Initialize the store
  useEffect(() => {
    createStore();
    setIsStoreInitialized(true);
  }, []);

  // We don't want to show the editor welcome guide as it is not relevant to emails
  useDisableWelcomeGuide();

  return (
    <>
      <LayoutStyles />
      {isStoreInitialized ? (
        <>
          <HeaderButtonSlot className="mailpoet-header-button-preview">
            <PreviewDropdown
              newsletterId={mailpoetData?.id ?? null}
              newsletterPreviewUrl={mailpoetData?.preview_url ?? null}
            />
          </HeaderButtonSlot>
          <HeaderButtonSlot>
            <NextButton newsletterId={mailpoetData?.id ?? null} />
          </HeaderButtonSlot>
          <SettingsSidebar />
        </>
      ) : null}
    </>
  );
}

function initializeEditor() {
  registerPlugin('mailpoet-email-editor', {
    render: Editor,
  });
}

export { initializeEditor };
