import classnames from 'classnames';
import { Component } from 'react';
import jQuery from 'jquery';
import PropTypes from 'prop-types';

import { Button } from 'common';
import { Listing } from 'listing/listing.jsx';
import { MailPoet } from 'mailpoet';
import { plusIcon } from 'common/button/icon/plus';
import { SegmentTags } from 'common/tag/tags';
import { Toggle } from 'common/form/toggle/toggle';
import { withNpsPoll } from 'nps_poll.jsx';
import { FormsHeading, onAddNewForm } from './heading';

const columns = [
  {
    name: 'name',
    label: MailPoet.I18n.t('formName'),
    sortable: true,
  },
  {
    name: 'segments',
    label: MailPoet.I18n.t('segments'),
  },
  {
    name: 'type',
    label: MailPoet.I18n.t('type'),
  },
  {
    name: 'status',
    label: MailPoet.I18n.t('status'),
  },
  {
    name: 'updated_at',
    label: MailPoet.I18n.t('updatedAt'),
    sortable: true,
  },
];

const messages = {
  onTrash: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = MailPoet.I18n.t('oneFormTrashed');
    } else {
      message = MailPoet.I18n.t('multipleFormsTrashed').replace(
        '%1$d',
        count.toLocaleString(),
      );
    }
    MailPoet.Notice.success(message);
  },
  onDelete: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = MailPoet.I18n.t('oneFormDeleted');
    } else {
      message = MailPoet.I18n.t('multipleFormsDeleted').replace(
        '%1$d',
        count.toLocaleString(),
      );
    }
    MailPoet.Notice.success(message);
  },
  onRestore: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = MailPoet.I18n.t('oneFormRestored');
    } else {
      message = MailPoet.I18n.t('multipleFormsRestored').replace(
        '%1$d',
        count.toLocaleString(),
      );
    }
    MailPoet.Notice.success(message);
  },
  onNoItemsFound: () => (
    <div className="mailpoet-forms-add-new-row">
      <p>{MailPoet.I18n.t('noItemsFound')}</p>
      <Button
        onClick={onAddNewForm}
        automationId="add_new_form"
        iconStart={plusIcon}
      >
        {MailPoet.I18n.t('new')}
      </Button>
    </div>
  ),
};

const bulkActions = [
  {
    name: 'trash',
    label: MailPoet.I18n.t('moveToTrash'),
    onSuccess: messages.onTrash,
  },
];

function getFormPlacement(settings) {
  const placements = [];
  /* eslint-disable camelcase */
  if (settings?.form_placement?.fixed_bar?.enabled === '1') {
    placements.push(MailPoet.I18n.t('placeFixedBarFormOnPages'));
  }
  if (settings?.form_placement?.below_posts?.enabled === '1') {
    placements.push(MailPoet.I18n.t('placeFormBellowPages'));
  }
  if (settings?.form_placement?.popup?.enabled === '1') {
    placements.push(MailPoet.I18n.t('placePopupFormOnPages'));
  }
  if (settings?.form_placement?.slide_in?.enabled === '1') {
    placements.push(MailPoet.I18n.t('placeSlideInFormOnPages'));
  }
  if (placements.length > 0) {
    return placements.join(', ');
  }
  /* eslint-enable camelcase */
  return MailPoet.I18n.t('placeFormOthers');
}

const itemActions = [
  {
    name: 'edit',
    className: 'mailpoet-hide-on-mobile',
    label: MailPoet.I18n.t('edit'),
    link: function link(item) {
      return (
        <a href={`admin.php?page=mailpoet-form-editor&id=${item.id}`}>
          {MailPoet.I18n.t('edit')}
        </a>
      );
    },
  },
  {
    name: 'duplicate',
    className: 'mailpoet-hide-on-mobile',
    label: MailPoet.I18n.t('duplicate'),
    onClick: function onClick(item, refresh) {
      return MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'forms',
        action: 'duplicate',
        data: {
          id: item.id,
        },
      })
        .done((response) => {
          const formName = response.data.name
            ? response.data.name
            : MailPoet.I18n.t('noName');
          MailPoet.Notice.success(
            MailPoet.I18n.t('formDuplicated').replace('%1$s', formName),
          );
          refresh();
        })
        .fail((response) => {
          if (response.errors.length > 0) {
            MailPoet.Notice.error(
              response.errors.map((error) => error.message),
              { scroll: true },
            );
          }
        });
    },
  },
  {
    name: 'trash',
    className: 'mailpoet-hide-on-mobile',
  },
];

class FormListComponent extends Component {
  updateStatus = (checked, e) => {
    // make the event persist so that we can still override the selected value
    // in the ajax callback
    e.persist();

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'forms',
      action: 'setStatus',
      data: {
        id: Number(e.target.getAttribute('data-id')),
        status: checked ? 'enabled' : 'disabled',
      },
    })
      .done((response) => {
        if (response.data.status === 'enabled') {
          MailPoet.Notice.success(MailPoet.I18n.t('formActivated'));
        }
      })
      .fail((response) => {
        MailPoet.Notice.showApiErrorNotice(response);

        // reset value to previous form's status
        e.target.checked = !checked;
      });
  };

  isItemInactive = (form) => form.status === 'disabled';

  renderStatus(form) {
    return (
      <div>
        <Toggle
          onCheck={this.updateStatus}
          data-id={form.id}
          dimension="small"
          defaultChecked={form.status === 'enabled'}
        />
        <p>
          {MailPoet.I18n.t('signups')}
          {': '}
          {form.signups.toLocaleString()}
        </p>
      </div>
    );
  }

  renderItem = (form, actions) => {
    const rowClasses = classnames(
      'manage-column',
      'column-primary',
      'has-row-actions',
    );

    const segments = window.mailpoet_segments.filter(
      (segment) => jQuery.inArray(segment.id, form.segments) !== -1,
    );

    const placement = getFormPlacement(form.settings);

    return (
      <>
        <td className={rowClasses}>
          <a
            className="mailpoet-listing-title"
            href={`admin.php?page=mailpoet-form-editor&id=${form.id}`}
          >
            {form.name ? form.name : `(${MailPoet.I18n.t('noName')})`}
          </a>
          {actions}
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('segments')}>
          <SegmentTags segments={segments} dimension="large">
            {form.settings.segments_selected_by === 'user' && (
              <span className="mailpoet-tags-prefix">
                {MailPoet.I18n.t('userChoice')}
              </span>
            )}
          </SegmentTags>
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('type')}>
          {placement}
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('status')}>
          {this.renderStatus(form)}
        </td>
        <td
          className="column-date mailpoet-hide-on-mobile"
          data-colname={MailPoet.I18n.t('updatedAt')}
        >
          {MailPoet.Date.short(form.updated_at)}
          <br />
          {MailPoet.Date.time(form.updated_at)}
        </td>
      </>
    );
  };

  render() {
    return (
      <div className="mailpoet-listing-no-actions-on-mobile">
        <FormsHeading />

        <Listing
          limit={window.mailpoet_listing_per_page}
          className="mailpoet-forms-listing"
          location={this.props.location}
          params={this.props.match.params}
          messages={messages}
          search={false}
          endpoint="forms"
          onRenderItem={this.renderItem}
          isItemInactive={this.isItemInactive}
          columns={columns}
          bulk_actions={bulkActions}
          item_actions={itemActions}
        />
      </div>
    );
  }
}

FormListComponent.propTypes = {
  location: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  match: PropTypes.shape({
    params: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  }).isRequired,
};
FormListComponent.displayName = 'FormList';
export const FormList = withNpsPoll(FormListComponent);
