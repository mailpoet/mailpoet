import { useCallback, useMemo, useState } from 'react';
import { DataViews, type Action, type View } from '@wordpress/dataviews';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button, Modal } from '@wordpress/components';
import { Notice } from '@wordpress/ui';
import {
  getDataViewsPreference,
  usePersistedDataViewsPreference,
  useDataViewsQuery,
  type ListingQueryParams,
} from 'common/dataviews';
import type { EmailPreset } from 'common/email-presets';
import { PageHeader } from 'common/page-header';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';
import {
  createPreset,
  deletePreset,
  duplicatePreset,
  getPresets,
  getTrashedPresetIds,
  renamePreset,
  restorePreset,
  trashPreset,
  type PresetsGroup,
} from './api';
import { presetFields } from './fields';
import { PresetNameForm, PresetPreviewModal } from './modals';

const PREFERENCE_KEY = 'email-templates-presets';

const DEFAULT_VIEW: View = {
  type: 'grid',
  perPage: 20,
  page: 1,
  sort: { field: 'updated_at', direction: 'desc' },
  fields: ['source', 'author', 'updated_at'],
  titleField: 'title',
  mediaField: 'thumbnail',
  descriptionField: 'description',
  showTitle: true,
  showMedia: true,
  showDescription: true,
  layout: { previewSize: 240 },
};

type NoticeState = {
  status: 'success' | 'error';
  message: string;
  // Presets that can be restored from the notice.
  undoIds?: number[];
};

function errorMessage(error: unknown): string {
  return (
    (error as Error)?.message ||
    __('The action could not be completed. Please try again.', 'mailpoet')
  );
}

// Waits for every request, then throws the first failure.
async function settleAll(requests: Promise<void>[]): Promise<void> {
  const results = await Promise.allSettled(requests);
  const failed = results.find(
    (result): result is PromiseRejectedResult => result.status === 'rejected',
  );
  if (failed) throw failed.reason;
}

export function EmailTemplatesPage() {
  const [group, setGroup] = useState<PresetsGroup>('all');
  const [selection, setSelection] = useState<string[]>([]);
  const [isEmptyingTrash, setIsEmptyingTrash] = useState(false);
  const [notice, setNotice] = useState<NoticeState | null>(null);
  const [previewTarget, setPreviewTarget] = useState<EmailPreset | null>(null);
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [initialView] = useState<View>(() =>
    getDataViewsPreference(PREFERENCE_KEY, DEFAULT_VIEW, presetFields),
  );

  const load = useCallback(
    (params: ListingQueryParams, signal?: AbortSignal) =>
      getPresets({ ...params, group }, signal),
    [group],
  );

  const {
    view,
    setView,
    items,
    meta,
    groups,
    isLoading,
    error: loadError,
    onChangeView,
    clearError,
    refresh,
  } = useDataViewsQuery<EmailPreset>({ initialView, load });
  const handleViewChange = usePersistedDataViewsPreference(
    PREFERENCE_KEY,
    view,
    onChangeView,
  );

  const counts = useMemo(() => {
    const result: Record<PresetsGroup, number> = { all: 0, trash: 0 };
    (groups ?? []).forEach(({ name, count }) => {
      if (name === 'all' || name === 'trash') result[name] = count;
    });
    return result;
  }, [groups]);

  const switchGroup = (next: PresetsGroup) => {
    if (next === group) return;
    setSelection([]);
    setGroup(next);
    setView((current) => ({ ...current, page: 1 }));
  };

  const runForEach = useCallback(
    async (
      presets: EmailPreset[],
      run: (id: number) => Promise<void>,
    ): Promise<boolean> => {
      try {
        await settleAll(presets.map((preset) => run(preset.id)));
        return true;
      } catch (error) {
        setNotice({ status: 'error', message: errorMessage(error) });
        return false;
      } finally {
        setSelection([]);
        refresh();
      }
    },
    [refresh],
  );

  const handleTrash = useCallback(
    async (presets: EmailPreset[]) => {
      if (!(await runForEach(presets, trashPreset))) return;
      setNotice({
        status: 'success',
        message:
          presets.length === 1
            ? sprintf(
                /* translators: %s is the preset name */
                __('"%s" moved to the Trash.', 'mailpoet'),
                presets[0].title,
              )
            : sprintf(
                /* translators: %d is the number of presets */
                _n(
                  '%d preset moved to the Trash.',
                  '%d presets moved to the Trash.',
                  presets.length,
                  'mailpoet',
                ),
                presets.length,
              ),
        undoIds: presets.map((preset) => preset.id),
      });
    },
    [runForEach],
  );

  const handleUndoTrash = async (ids: number[]) => {
    setNotice(null);
    try {
      await settleAll(ids.map((id) => restorePreset(id)));
    } catch (error) {
      setNotice({ status: 'error', message: errorMessage(error) });
    } finally {
      refresh();
    }
  };

  const handleRestore = useCallback(
    async (presets: EmailPreset[]) => {
      if (!(await runForEach(presets, restorePreset))) return;
      setNotice({
        status: 'success',
        message: sprintf(
          /* translators: %d is the number of presets */
          _n(
            '%d preset restored from the Trash.',
            '%d presets restored from the Trash.',
            presets.length,
            'mailpoet',
          ),
          presets.length,
        ),
      });
    },
    [runForEach],
  );

  const handleDelete = useCallback(
    async (presets: EmailPreset[]) => {
      if (
        // eslint-disable-next-line no-alert -- the same confirm as the other listings
        !window.confirm(
          sprintf(
            /* translators: %d is the number of presets */
            _n(
              'Delete %d preset permanently? This cannot be undone.',
              'Delete %d presets permanently? This cannot be undone.',
              presets.length,
              'mailpoet',
            ),
            presets.length,
          ),
        )
      ) {
        return;
      }
      if (!(await runForEach(presets, deletePreset))) return;
      setNotice({
        status: 'success',
        message: sprintf(
          /* translators: %d is the number of presets */
          _n(
            '%d preset permanently deleted.',
            '%d presets permanently deleted.',
            presets.length,
            'mailpoet',
          ),
          presets.length,
        ),
      });
    },
    [runForEach],
  );

  const handleEmptyTrash = async () => {
    if (
      // eslint-disable-next-line no-alert -- the same confirm as the other listings
      !window.confirm(
        __(
          'Delete all presets in the Trash permanently? This cannot be undone.',
          'mailpoet',
        ),
      )
    ) {
      return;
    }
    // Undo during the delete would restore presets that are about to go.
    setNotice(null);
    setIsEmptyingTrash(true);
    try {
      const ids = await getTrashedPresetIds();
      await settleAll(ids.map((id) => deletePreset(id)));
      setNotice({
        status: 'success',
        message: __('The Trash was emptied.', 'mailpoet'),
      });
    } catch (error) {
      setNotice({ status: 'error', message: errorMessage(error) });
    } finally {
      setIsEmptyingTrash(false);
      setSelection([]);
      refresh();
    }
  };

  const handleDuplicate = useCallback(
    async (preset: EmailPreset) => {
      try {
        const copy = await duplicatePreset(preset.id);
        setNotice({
          status: 'success',
          message: sprintf(
            /* translators: %s is the name of the new preset */
            __('Preset duplicated as "%s".', 'mailpoet'),
            copy.title,
          ),
        });
      } catch (error) {
        setNotice({ status: 'error', message: errorMessage(error) });
      } finally {
        refresh();
      }
    },
    [refresh],
  );

  const handleCreate = useCallback(async (title: string): Promise<boolean> => {
    try {
      const preset = await createPreset(title);
      window.location.href = preset.edit_url;
      return true;
    } catch (error) {
      setNotice({ status: 'error', message: errorMessage(error) });
      return false;
    }
  }, []);

  const handleRename = useCallback(
    async (preset: EmailPreset, title: string): Promise<boolean> => {
      try {
        await renamePreset(preset.id, title);
        setNotice({
          status: 'success',
          message: __('Preset renamed.', 'mailpoet'),
        });
        refresh();
        return true;
      } catch (error) {
        setNotice({ status: 'error', message: errorMessage(error) });
        return false;
      }
    },
    [refresh],
  );

  const actions = useMemo<Action<EmailPreset>[]>(() => {
    if (group === 'trash') {
      return [
        {
          id: 'restore',
          label: __('Restore', 'mailpoet'),
          isPrimary: true,
          icon: 'backup',
          supportsBulk: true,
          callback: (presets) => void handleRestore(presets),
        },
        {
          id: 'delete',
          label: __('Delete permanently', 'mailpoet'),
          isDestructive: true,
          supportsBulk: true,
          callback: (presets) => void handleDelete(presets),
        },
      ];
    }
    return [
      {
        id: 'edit',
        label: __('Edit', 'mailpoet'),
        isPrimary: true,
        icon: 'edit',
        supportsBulk: false,
        callback: ([preset]) => {
          window.location.href = preset.edit_url;
        },
      },
      {
        id: 'preview',
        label: __('Preview', 'mailpoet'),
        supportsBulk: false,
        callback: ([preset]) => setPreviewTarget(preset),
      },
      {
        id: 'duplicate',
        label: __('Duplicate', 'mailpoet'),
        supportsBulk: false,
        callback: ([preset]) => void handleDuplicate(preset),
      },
      {
        id: 'rename',
        label: __('Rename', 'mailpoet'),
        supportsBulk: false,
        modalHeader: __('Rename preset', 'mailpoet'),
        modalFocusOnMount: 'firstContentElement',
        RenderModal: ({ items: [preset], closeModal }) => (
          <PresetNameForm
            defaultTitle={preset.title}
            submitLabel={__('Save', 'mailpoet')}
            onSubmit={(title) => handleRename(preset, title)}
            onClose={() => closeModal?.()}
          />
        ),
      },
      {
        id: 'trash',
        label: __('Move to trash', 'mailpoet'),
        isDestructive: true,
        supportsBulk: true,
        callback: (presets) => void handleTrash(presets),
      },
    ];
  }, [
    group,
    handleDelete,
    handleDuplicate,
    handleRename,
    handleRestore,
    handleTrash,
  ]);

  let emptyMessage: string = __(
    'No presets yet. Save an email as a preset from the email editor or the Emails page.',
    'mailpoet',
  );
  if (view.search) {
    emptyMessage = __('No presets found.', 'mailpoet');
  } else if (group === 'trash') {
    emptyMessage = __('The Trash is empty.', 'mailpoet');
  }

  const tabs: { name: PresetsGroup; label: string }[] = [
    { name: 'all', label: __('Presets', 'mailpoet') },
    { name: 'trash', label: __('Trash', 'mailpoet') },
  ];

  const paginationInfo = useMemo(
    () => ({ totalItems: meta.count, totalPages: meta.pages }),
    [meta],
  );

  return (
    <>
      <TopBarWithBoundary />
      <PageHeader heading={__('Email templates', 'mailpoet')}>
        <Button
          className="page-title-action"
          onClick={() => setIsAddModalOpen(true)}
          data-automation-id="email_templates_add_preset"
        >
          {__('Add preset', 'mailpoet')}
        </Button>
      </PageHeader>

      {loadError && (
        <Notice.Root intent="error">
          <Notice.Description>{loadError}</Notice.Description>
          <Notice.CloseIconButton onClick={clearError} />
        </Notice.Root>
      )}
      {notice && (
        <Notice.Root intent={notice.status}>
          <Notice.Description>{notice.message}</Notice.Description>
          {notice.undoIds && (
            <Notice.Actions>
              <Notice.ActionButton
                onClick={() => void handleUndoTrash(notice.undoIds ?? [])}
              >
                {__('Undo', 'mailpoet')}
              </Notice.ActionButton>
            </Notice.Actions>
          )}
          <Notice.CloseIconButton onClick={() => setNotice(null)} />
        </Notice.Root>
      )}

      <div
        className="mailpoet-dataviews mailpoet-email-templates-dataviews"
        data-automation-id="email_templates_listing"
      >
        <DataViews<EmailPreset>
          data={items}
          fields={presetFields}
          view={view}
          onChangeView={handleViewChange}
          actions={actions}
          paginationInfo={paginationInfo}
          defaultLayouts={{ grid: {}, table: {} }}
          getItemId={(item) => String(item.id)}
          selection={selection}
          onChangeSelection={setSelection}
          isLoading={isLoading}
          empty={
            <div className="mailpoet-email-templates__empty">
              {emptyMessage}
            </div>
          }
        >
          <div className="mailpoet-dataviews__toolbar mailpoet-email-templates__toolbar">
            <div
              className="mailpoet-email-templates__tabs"
              role="tablist"
              aria-label={__('Email templates', 'mailpoet')}
            >
              {tabs
                .filter(
                  ({ name }) =>
                    name !== 'trash' || counts.trash > 0 || group === 'trash',
                )
                .map(({ name, label }) => (
                  <button
                    key={name}
                    type="button"
                    role="tab"
                    aria-selected={group === name}
                    className={`mailpoet-email-templates__tab${
                      group === name ? ' is-active' : ''
                    }`}
                    data-automation-id={`email_templates_tab_${name}`}
                    onClick={() => switchGroup(name)}
                  >
                    {label}
                    <span className="mailpoet-email-templates__tab-count">
                      {counts[name]}
                    </span>
                  </button>
                ))}
            </div>
            <div className="mailpoet-dataviews__toolbar-end">
              {group === 'trash' && counts.trash > 0 && (
                <Button
                  __next40pxDefaultSize
                  variant="secondary"
                  isDestructive
                  isBusy={isEmptyingTrash}
                  disabled={isEmptyingTrash}
                  onClick={() => void handleEmptyTrash()}
                >
                  {__('Empty Trash', 'mailpoet')}
                </Button>
              )}
              <DataViews.Search label={__('Search presets', 'mailpoet')} />
              <DataViews.ViewConfig />
            </div>
          </div>
          <DataViews.Layout />
          <DataViews.Footer />
        </DataViews>
      </div>

      {isAddModalOpen && (
        <Modal
          title={__('Add preset', 'mailpoet')}
          onRequestClose={() => setIsAddModalOpen(false)}
          focusOnMount="firstContentElement"
        >
          <PresetNameForm
            defaultTitle=""
            submitLabel={__('Add preset', 'mailpoet')}
            onSubmit={handleCreate}
            onClose={() => setIsAddModalOpen(false)}
          />
        </Modal>
      )}
      {previewTarget && (
        <PresetPreviewModal
          preset={previewTarget}
          onClose={() => setPreviewTarget(null)}
        />
      )}
    </>
  );
}
