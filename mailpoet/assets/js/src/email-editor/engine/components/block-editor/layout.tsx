import {
  // @ts-expect-error No types for this exist yet.
  __experimentalUseResizeCanvas as useResizeCanvas,
  BlockSelectionClearer,
} from '@wordpress/block-editor';

import {
  UnsavedChangesWarning,
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  privateApis as editorPrivateApis,
  store as editorStore,
} from '@wordpress/editor';
import { uploadMedia } from '@wordpress/media-utils';
import classnames from 'classnames';
import { useSelect, useDispatch } from '@wordpress/data';
import {
  ComplementaryArea,
  FullscreenMode,
  InterfaceSkeleton,
} from '@wordpress/interface';

import './index.scss';
import { store as coreStore } from '@wordpress/core-data';
import { storeName } from '../../store';
import { useEmailCss } from '../../hooks';
import { AutosaveMonitor } from '../autosave';
import { BlockCompatibilityWarnings, Sidebar } from '../sidebar';
import { Header } from '../header';
import { ListviewSidebar } from '../listview-sidebar/listview-sidebar';
import { InserterSidebar } from '../inserter-sidebar/inserter-sidebar';
import { EditorNotices, EditorSnackbars, SentEmailNotice } from '../notices';
import { StylesSidebar } from '../styles-sidebar';
import { FooterCredit } from './footer-credit';
import { unlock } from '../../../lock-unlock';

const { EditorCanvas } = unlock(editorPrivateApis);

export function Layout() {
  const {
    isFullscreenActive,
    isSidebarOpened,
    initialSettings,
    previewDeviceType,
    isInserterSidebarOpened,
    isListviewSidebarOpened,
    isEmailLoaded,
    canUserEditMedia,
    hasFixedToolbar,
    focusMode,
    styles,
    cdnUrl,
    isPremiumPluginActive,
    isEditingTemplate,
    currentTemplate,
  } = useSelect(
    (select) => ({
      isFullscreenActive: select(storeName).isFeatureActive('fullscreenMode'),
      isSidebarOpened: select(storeName).isSidebarOpened(),
      isInserterSidebarOpened: select(storeName).isInserterSidebarOpened(),
      isListviewSidebarOpened: select(storeName).isListviewSidebarOpened(),
      initialSettings: select(storeName).getInitialEditorSettings(),
      previewDeviceType: select(storeName).getPreviewState().deviceType,
      isEmailLoaded: select(storeName).isEmailLoaded(),
      canUserEditMedia: select(coreStore).canUser('create', 'media'),
      hasFixedToolbar: select(storeName).isFeatureActive('fixedToolbar'),
      focusMode: select(storeName).isFeatureActive('focusMode'),
      styles: select(storeName).getStyles(),
      cdnUrl: select(storeName).getCdnUrl(),
      isPremiumPluginActive: select(storeName).isPremiumPluginActive(),
      isEditingTemplate:
        // @ts-expect-error No types for this exist yet.
        select(editorStore).getCurrentPostType() === 'wp_template',
      currentTemplate: select(editorStore).getCurrentTemplateId(),
    }),
    [],
  );

  const { toggleInserterSidebar } = useDispatch(storeName);

  const [emailCss] = useEmailCss();
  const className = classnames('edit-post-layout', {
    'is-sidebar-opened': isSidebarOpened,
  });

  const contentWrapperStyles = useResizeCanvas(previewDeviceType);

  if (isEditingTemplate) {
    contentWrapperStyles.height = '100%';
  }

  // Styles for the canvas. Based on template-canvas.php, this equates to the body element.
  const canvasStyles = {
    background:
      previewDeviceType === 'Desktop' ? styles.color.background : 'transparent',
    fontFamily: styles.typography.fontFamily,
    transition: 'all 0.3s ease 0s',
  };

  const settings = {
    ...initialSettings,
    mediaUpload: canUserEditMedia ? uploadMedia : null,
    hasFixedToolbar,
    focusMode,
  };

  // Do not render editor if email is not loaded yet.
  if (!isEmailLoaded || currentTemplate === null) {
    return null;
  }

  const disableIframe = !isEditingTemplate;

  return (
    <>
      <FullscreenMode isActive={isFullscreenActive} />
      <UnsavedChangesWarning />
      <AutosaveMonitor />
      <SentEmailNotice />
      <Sidebar />
      <StylesSidebar />
      <InterfaceSkeleton
        className={className}
        header={<Header />}
        editorNotices={<EditorNotices />}
        notices={<EditorSnackbars />}
        content={
          <>
            <EditorNotices />
            <BlockSelectionClearer
              className="visual-editor"
              style={canvasStyles}
              onClick={() => {
                // Clear inserter sidebar when canvas is clicked.
                if (isInserterSidebarOpened) {
                  void toggleInserterSidebar();
                }
              }}
            >
              <div className="visual-editor__email_layout_wrapper">
                <div
                  className={classnames(
                    'visual-editor__email_content_wrapper',
                    {
                      'is-mobile-preview': previewDeviceType === 'Mobile',
                      'is-desktop-preview': previewDeviceType === 'Desktop',
                    },
                  )}
                  style={contentWrapperStyles}
                >
                  <EditorCanvas
                    disableIframe={disableIframe}
                    styles={[...settings.styles, ...emailCss]}
                    autoFocus
                    className="has-global-padding"
                  />
                  {!isPremiumPluginActive && !isEditingTemplate && (
                    <div className="visual-editor__email_footer">
                      <FooterCredit
                        logoSrc={`${cdnUrl}email-editor/logo-footer.png`}
                      />
                    </div>
                  )}
                </div>
              </div>
            </BlockSelectionClearer>
          </>
        }
        sidebar={<ComplementaryArea.Slot scope={storeName} />}
        secondarySidebar={
          (isInserterSidebarOpened && <InserterSidebar />) ||
          (isListviewSidebarOpened && <ListviewSidebar />)
        }
      />
      {/* Rendering Warning component here ensures that the warning is displayed under the border configuration. */}
      <BlockCompatibilityWarnings />
    </>
  );
}
