import {
  // @ts-expect-error No types for this exist yet.
  __experimentalUseResizeCanvas as useResizeCanvas,
  BlockSelectionClearer,
  // @ts-expect-error No types for this exist yet.
  __unstableEditorStyles as EditorStyles,
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
import { useSelect } from '@wordpress/data';
import {
  ComplementaryArea,
  FullscreenMode,
  InterfaceSkeleton,
} from '@wordpress/interface';

import './index.scss';
import { store as coreStore } from '@wordpress/core-data';
import { storeName } from '../../store';
import { AutosaveMonitor } from '../autosave';
import { BlockCompatibilityWarnings, Sidebar } from '../sidebar';
import { Header } from '../header';
import { ListviewSidebar } from '../listview-sidebar/listview-sidebar';
import { InserterSidebar } from '../inserter-sidebar/inserter-sidebar';
import { EditorNotices, EditorSnackbars, SentEmailNotice } from '../notices';
import { StylesSidebar, ThemeStyles } from '../styles-sidebar';
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
    layout,
    cdnUrl,
    isPremiumPluginActive,
    isEditingTemplate,
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
      layout: select(storeName).getLayout(),
      cdnUrl: select(storeName).getCdnUrl(),
      isPremiumPluginActive: select(storeName).isPremiumPluginActive(),
      isEditingTemplate:
        // @ts-expect-error No types for this exist yet.
        select(editorStore).getCurrentPostType() === 'wp_template',
    }),
    [],
  );

  const className = classnames('edit-post-layout', {
    'is-sidebar-opened': isSidebarOpened,
  });

  const layoutBackground = styles.color.background.layout;

  let contentWrapperStyles = useResizeCanvas(previewDeviceType);

  // Adjust the inline styles for desktop preview. We want to set email width and center it.
  if (previewDeviceType === 'Desktop') {
    contentWrapperStyles = {
      ...contentWrapperStyles,
      height: 'auto',
      width: layout.contentSize,
      display: 'flex',
      flexFlow: 'column',
    };
  }

  // 72px top to place the email document nicely vertically in canvas. Same value is used for title in WP Post editor.
  // We use only 20px bottom to mitigate the issue with inserter popup displaying below the fold.
  contentWrapperStyles.margin = '72px auto 20px auto';
  delete contentWrapperStyles.marginLeft;
  delete contentWrapperStyles.marginTop;
  delete contentWrapperStyles.marginBottom;
  delete contentWrapperStyles.marginRight;

  // Styles for the canvas. Based on template-canvas.php, this equates to the body element.
  const canvasStyles = {
    background:
      previewDeviceType === 'Desktop' ? layoutBackground : 'transparent',
  };

  // Styles for the content wrapper. Based on template-canvas.php, this equates to the .email_layout_wrapper element. We don't add padding because that is taken care of by the editor-styles-wrapper div.
  const contentAreaStyles = {
    fontFamily: styles.typography.fontFamily,
    background: styles.color.background.content,
    transition: 'all 0.3s ease 0s',
  };

  const settings = {
    ...initialSettings,
    mediaUpload: canUserEditMedia ? uploadMedia : null,
    hasFixedToolbar,
    focusMode,
  };

  // Do not render editor if email is not loaded yet.
  if (!isEmailLoaded) {
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
            <div className="visual-editor" style={canvasStyles}>
              <div className="visual-editor__email_layout_wrapper">
                <div
                  style={contentWrapperStyles}
                  className={classnames({
                    'is-mobile-preview': previewDeviceType === 'Mobile',
                    'is-desktop-preview': previewDeviceType === 'Desktop',
                  })}
                >
                  <BlockSelectionClearer
                    className="visual-editor__email_content_wrapper"
                    style={contentAreaStyles}
                  >
                    <ThemeStyles />
                    <EditorStyles
                      styles={settings.styles}
                      scope=".editor-styles-wrapper"
                    />
                    <EditorCanvas
                      disableIframe={disableIframe}
                      styles={[]}
                      autoFocus
                      className="has-global-padding"
                    />
                  </BlockSelectionClearer>
                </div>
                <div className="visual-editor__email_footer">
                  {!isPremiumPluginActive && (
                    <FooterCredit
                      logoSrc={`${cdnUrl}email-editor/logo-footer.png`}
                    />
                  )}
                </div>
              </div>
            </div>
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
