import { createRegistrySelector } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { store as interfaceStore } from '@wordpress/interface';
import { store as editorStore } from '@wordpress/editor';
import { store as preferencesStore } from '@wordpress/preferences';
import { serialize, BlockInstance } from '@wordpress/blocks';
import { storeName } from './constants';
import { State, Feature } from './types';

export const isFeatureActive = createRegistrySelector(
  (select) =>
    (_, feature: Feature): boolean =>
      !!select(preferencesStore).get(storeName, feature),
);

export const isSidebarOpened = createRegistrySelector(
  (select) => (): boolean =>
    !!select(interfaceStore).getActiveComplementaryArea(storeName),
);

export const hasEdits = createRegistrySelector((select) => (): boolean => {
  const postId = select(storeName).getEmailPostId();
  return !!select(coreDataStore).hasEditsForEntityRecord(
    'postType',
    'mailpoet_email',
    postId,
  );
});

export const isEmailLoaded = createRegistrySelector((select) => (): boolean => {
  const postId = select(storeName).getEmailPostId();
  return !!select(coreDataStore).getEntityRecord(
    'postType',
    'mailpoet_email',
    postId,
  );
});

export const isSaving = createRegistrySelector((select) => (): boolean => {
  const postId = select(storeName).getEmailPostId();
  return !!select(coreDataStore).isSavingEntityRecord(
    'postType',
    'mailpoet_email',
    postId,
  );
});

export const isEmpty = createRegistrySelector((select) => (): boolean => {
  const postId = select(storeName).getEmailPostId();

  const post = select(coreDataStore).getEntityRecord(
    'postType',
    'mailpoet_email',
    postId,
  );
  if (!post) return true;

  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  const { content, mailpoet_data: mailpoetData, title } = post;
  return (
    !content.raw &&
    !mailpoetData.subject &&
    !mailpoetData.preheader &&
    !title.raw
  );
});

export const hasEmptyContent = createRegistrySelector(
  (select) => (): boolean => {
    const postId = select(storeName).getEmailPostId();

    const post = select(coreDataStore).getEntityRecord(
      'postType',
      'mailpoet_email',
      postId,
    );
    if (!post) return true;

    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    const { content } = post;
    return !content.raw;
  },
);

export const isEmailSent = createRegistrySelector((select) => (): boolean => {
  const postId = select(storeName).getEmailPostId();

  const post = select(coreDataStore).getEntityRecord(
    'postType',
    'mailpoet_email',
    postId,
  );
  if (!post) return false;

  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  const status = post.status;
  return status === 'sent';
});

/**
 * Returns the content of the email being edited.
 *
 * @param {Object} state Global application state.
 * @return {string} Post content.
 */
export const getEditedEmailContent = createRegistrySelector(
  (select) => (): string => {
    const postId = select(storeName).getEmailPostId();
    const record = select(coreDataStore).getEditedEntityRecord(
      'postType',
      'mailpoet_email',
      postId,
    ) as unknown as
      | { content: string | unknown; blocks: BlockInstance[] }
      | undefined;

    if (record) {
      if (record?.content && typeof record.content === 'function') {
        return record.content(record) as string;
      }
      if (record?.blocks) {
        return serialize(record.blocks);
      }
      if (record?.content) {
        return record.content as string;
      }
    }
    return '';
  },
);

/**
 * COPIED FROM https://github.com/WordPress/gutenberg/blob/9c6d4fe59763b188d27ad937c2f0daa39e4d9341/packages/edit-post/src/store/selectors.js
 * Retrieves the template of the currently edited post.
 *
 * @return {Object?} Post Template.
 */
export const getEditedPostTemplate = createRegistrySelector((select) => () => {
  const currentTemplate =
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    select(editorStore).getEditedPostAttribute('template');

  if (currentTemplate) {
    const templateWithSameSlug = select(coreDataStore)
      .getEntityRecords('postType', 'wp_template', { per_page: -1 })
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore
      ?.find((template) => template.slug === currentTemplate);

    if (!templateWithSameSlug) {
      return templateWithSameSlug;
    }

    return select(coreDataStore).getEditedEntityRecord(
      'postType',
      'wp_template',

      // @ts-expect-error getEditedPostAttribute
      // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
      templateWithSameSlug.id,
    );
  }

  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  const defaultTemplateId = select(coreDataStore).getDefaultTemplateId({
    slug: 'email-general',
  });

  return select(coreDataStore).getEditedEntityRecord(
    'postType',
    'wp_template',
    // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
    defaultTemplateId,
  );
});

export function getEmailPostId(state: State): number {
  return state.postId;
}

export function isInserterSidebarOpened(state: State): boolean {
  return state.inserterSidebar.isOpened;
}

export function isListviewSidebarOpened(state: State): boolean {
  return state.listviewSidebar.isOpened;
}

export function getSettingsSidebarActiveTab(state: State): string {
  return state.settingsSidebar.activeTab;
}

export function getInitialEditorSettings(
  state: State,
): State['editorSettings'] {
  return state.editorSettings;
}

export function getPaletteColors(
  state: State,
): State['editorSettings']['__experimentalFeatures']['color']['palette'] {
  // eslint-disable-next-line no-underscore-dangle
  return state.editorSettings.__experimentalFeatures.color.palette;
}

export function getPreviewState(state: State): State['preview'] {
  return state.preview;
}

export function getStyles(state: State): State['theme']['styles'] {
  return state.theme.styles;
}

export function getLayout(state: State): State['layout'] {
  return state.layout;
}

export function getAutosaveInterval(state: State): State['autosaveInterval'] {
  return state.autosaveInterval;
}

export function getCdnUrl(state: State): State['cdnUrl'] {
  return state.cdnUrl;
}

export function isPremiumPluginActive(state: State): boolean {
  return state.isPremiumPluginActive;
}

export function getTheme(state: State): State['theme'] {
  return state.theme;
}

export function getUrls(state: State): State['urls'] {
  return state.urls;
}
