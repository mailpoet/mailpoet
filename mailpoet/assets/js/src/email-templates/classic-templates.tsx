import { useCallback, useMemo, useState } from 'react';
import {
  DataViews,
  filterSortAndPaginate,
  type Action,
  type Field,
  type View,
} from '@wordpress/dataviews';
import { Modal } from '@wordpress/components';
import { Badge, EmptyState } from '@wordpress/ui';
import { __, sprintf } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import {
  getDataViewsPreference,
  usePersistedDataViewsPreference,
} from 'common/dataviews';
import {
  deleteClassicTemplate,
  duplicateClassicTemplate,
  getClassicTemplateEditUrl,
  getClassicTemplateType,
  renameClassicTemplate,
  type ClassicTemplate,
} from './classic-api';
import { PresetNameForm } from './modals';

const PREFERENCE_KEY = 'email-templates-classic';

const DEFAULT_VIEW: View = {
  type: 'grid',
  perPage: 20,
  page: 1,
  sort: { field: 'updated_at', direction: 'desc' },
  fields: ['source', 'type', 'updated_at'],
  titleField: 'name',
  mediaField: 'thumbnail',
  showTitle: true,
  showMedia: true,
  layout: { previewSize: 240 },
};

const typeLabels: Record<string, string> = {
  standard: __('Newsletter', 'mailpoet'),
  welcome: __('Welcome email', 'mailpoet'),
  notification: __('Post notification', 'mailpoet'),
  re_engagement: __('Re-engagement email', 'mailpoet'),
  woocommerce: __('WooCommerce', 'mailpoet'),
  automatic: __('Automatic email', 'mailpoet'),
  automation: __('Automation email', 'mailpoet'),
  automation_transactional: __('Automation transactional email', 'mailpoet'),
  wc_transactional: __('WooCommerce transactional email', 'mailpoet'),
  other: __('Other', 'mailpoet'),
};

function getCategory(template: ClassicTemplate): string {
  const type = getClassicTemplateType(template);
  return type in typeLabels ? type : 'other';
}

const fields: Field<ClassicTemplate>[] = [
  {
    id: 'name',
    label: __('Name', 'mailpoet'),
    type: 'text',
    enableSorting: true,
    enableHiding: false,
    enableGlobalSearch: true,
  },
  {
    id: 'thumbnail',
    label: __('Preview', 'mailpoet'),
    enableSorting: false,
    render: ({ item }) => (
      <div className="mailpoet-email-template-thumbnail">
        {item.thumbnail && <img src={item.thumbnail} alt="" loading="lazy" />}
      </div>
    ),
  },
  {
    id: 'source',
    label: __('Source', 'mailpoet'),
    enableSorting: false,
    getValue: ({ item }) => (item.readonly ? 'mailpoet' : 'saved'),
    elements: [
      { value: 'mailpoet', label: __('MailPoet', 'mailpoet') },
      { value: 'saved', label: __('Yours', 'mailpoet') },
    ],
    filterBy: { operators: ['isAny'] },
    render: ({ item }) =>
      item.readonly ? (
        <Badge>{__('MailPoet', 'mailpoet')}</Badge>
      ) : (
        <Badge intent="stable">{__('Yours', 'mailpoet')}</Badge>
      ),
  },
  {
    id: 'type',
    label: __('Category', 'mailpoet'),
    enableSorting: false,
    getValue: ({ item }) => getCategory(item),
    elements: Object.entries(typeLabels).map(([value, label]) => ({
      value,
      label,
    })),
    filterBy: { operators: ['isAny'] },
  },
  {
    id: 'updated_at',
    label: __('Last modified', 'mailpoet'),
    type: 'datetime',
    enableSorting: true,
    // MailPoet templates are rewritten on every plugin update.
    getValue: ({ item }) => (item.readonly ? null : item.updated_at),
    render: ({ item }) =>
      !item.readonly && item.updated_at ? (
        <span>{MailPoet.Date.short(item.updated_at)}</span>
      ) : (
        <span>—</span>
      ),
  },
];

type Props = {
  templates: ClassicTemplate[];
  isLoading: boolean;
  refresh: () => void;
  onNotice: (status: 'success' | 'error', message: string) => void;
};

export function ClassicTemplates({
  templates,
  isLoading,
  refresh,
  onNotice,
}: Props) {
  const [view, setView] = useState<View>(() =>
    getDataViewsPreference(PREFERENCE_KEY, DEFAULT_VIEW, fields),
  );
  const handleViewChange = usePersistedDataViewsPreference(
    PREFERENCE_KEY,
    view,
    setView,
  );
  const [previewTarget, setPreviewTarget] = useState<ClassicTemplate | null>(
    null,
  );

  const { data, paginationInfo } = useMemo(
    () => filterSortAndPaginate(templates, view, fields),
    [templates, view],
  );

  const run = useCallback(
    async (action: () => Promise<string | void>) => {
      try {
        const message = await action();
        if (message) onNotice('success', message);
      } catch (error) {
        onNotice('error', (error as Error).message);
      } finally {
        refresh();
      }
    },
    [onNotice, refresh],
  );

  const actions = useMemo<Action<ClassicTemplate>[]>(() => {
    const isSaved = (template: ClassicTemplate) => !template.readonly;
    return [
      {
        id: 'edit',
        label: __('Edit', 'mailpoet'),
        isPrimary: true,
        icon: 'edit',
        supportsBulk: false,
        isEligible: isSaved,
        callback: ([template]) => {
          getClassicTemplateEditUrl(template.id)
            .then((url) => {
              window.location.href = url;
            })
            .catch((error: Error) => onNotice('error', error.message));
        },
      },
      {
        id: 'duplicate-and-edit',
        label: __('Duplicate and edit', 'mailpoet'),
        isPrimary: true,
        icon: 'edit',
        supportsBulk: false,
        isEligible: (template) => template.readonly,
        callback: ([template]) => {
          duplicateClassicTemplate(template.id)
            .then((copy) => getClassicTemplateEditUrl(copy.id))
            .then((url) => {
              window.location.href = url;
            })
            .catch((error: Error) => {
              onNotice('error', error.message);
              refresh();
            });
        },
      },
      {
        id: 'preview',
        label: __('Preview', 'mailpoet'),
        supportsBulk: false,
        isEligible: (template) => !!template.thumbnail,
        callback: ([template]) => setPreviewTarget(template),
      },
      {
        id: 'duplicate',
        label: __('Duplicate', 'mailpoet'),
        supportsBulk: false,
        callback: ([template]) =>
          void run(async () => {
            const copy = await duplicateClassicTemplate(template.id);
            return sprintf(
              /* translators: %s is the name of the new template */
              __('Template duplicated as "%s".', 'mailpoet'),
              copy.name,
            );
          }),
      },
      {
        id: 'rename',
        label: __('Rename', 'mailpoet'),
        supportsBulk: false,
        isEligible: isSaved,
        modalHeader: __('Rename template', 'mailpoet'),
        modalFocusOnMount: 'firstContentElement',
        RenderModal: ({ items: [template], closeModal }) => (
          <PresetNameForm
            label={__('Template name', 'mailpoet')}
            defaultTitle={template.name}
            submitLabel={__('Save', 'mailpoet')}
            onSubmit={async (name) => {
              try {
                await renameClassicTemplate(template.id, name);
                onNotice('success', __('Template renamed.', 'mailpoet'));
                refresh();
                return true;
              } catch (error) {
                onNotice('error', (error as Error).message);
                return false;
              }
            }}
            onClose={() => closeModal?.()}
          />
        ),
      },
      {
        id: 'delete',
        label: __('Delete permanently', 'mailpoet'),
        isDestructive: true,
        supportsBulk: false,
        isEligible: isSaved,
        callback: ([template]) => {
          if (
            // eslint-disable-next-line no-alert -- the same confirm as the presets tab
            !window.confirm(
              sprintf(
                /* translators: %s is the template name */
                __(
                  'Delete "%s" permanently? This cannot be undone.',
                  'mailpoet',
                ),
                template.name,
              ),
            )
          ) {
            return;
          }
          void run(async () => {
            await deleteClassicTemplate(template.id);
            return __('Template permanently deleted.', 'mailpoet');
          });
        },
      },
    ];
  }, [onNotice, refresh, run]);

  return (
    <div
      className="mailpoet-dataviews mailpoet-email-templates-dataviews"
      data-automation-id="email_templates_classic_listing"
    >
      <DataViews<ClassicTemplate>
        data={data}
        fields={fields}
        view={view}
        onChangeView={handleViewChange}
        actions={actions}
        paginationInfo={paginationInfo}
        defaultLayouts={{ grid: {}, table: {} }}
        getItemId={(item) => String(item.id)}
        isLoading={isLoading}
        empty={
          <EmptyState.Root>
            <EmptyState.Description>
              {__('No templates found.', 'mailpoet')}
            </EmptyState.Description>
          </EmptyState.Root>
        }
      >
        <div className="mailpoet-dataviews__toolbar">
          <div className="mailpoet-dataviews__toolbar-end">
            <DataViews.Search label={__('Search templates', 'mailpoet')} />
            <DataViews.FiltersToggle />
            <DataViews.ViewConfig />
          </div>
        </div>
        <DataViews.FiltersToggled className="mailpoet-dataviews__filters" />
        <DataViews.Layout />
        <DataViews.Footer />
      </DataViews>

      {previewTarget?.thumbnail && (
        <Modal
          title={previewTarget.name}
          onRequestClose={() => setPreviewTarget(null)}
          size="large"
          className="mailpoet-email-templates-preview-modal"
        >
          <img
            className="mailpoet-email-templates-preview-image"
            src={previewTarget.thumbnail}
            alt={previewTarget.name}
          />
        </Modal>
      )}
    </div>
  );
}
