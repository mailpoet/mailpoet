import React from 'react';
import classNames from 'classnames';
import MailPoet from 'mailpoet';
import jQuery from 'jquery';
import PropTypes from 'prop-types';
import Listing from 'listing/listing.jsx';
import withNpsPoll from 'nps_poll.jsx';
import Tags from 'common/tag/tags';

const columns = [
  {
    name: 'name',
    label: MailPoet.I18n.t('formName'),
    sortable: true,
  },
  {
    name: 'status',
    label: MailPoet.I18n.t('status'),
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
      message = (
        MailPoet.I18n.t('oneFormTrashed')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleFormsTrashed')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onDelete: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneFormDeleted')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleFormsDeleted')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onRestore: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneFormRestored')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleFormsRestored')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
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
    label: MailPoet.I18n.t('edit'),
    link: function link(item) {
      return (
        <a href={`admin.php?page=mailpoet-form-editor&id=${item.id}`}>{MailPoet.I18n.t('edit')}</a>
      );
    },
  },
  {
    name: 'duplicate',
    label: MailPoet.I18n.t('duplicate'),
    onClick: function onClick(item, refresh) {
      return MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'forms',
        action: 'duplicate',
        data: {
          id: item.id,
        },
      }).done((response) => {
        const formName = response.data.name ? response.data.name : MailPoet.I18n.t('noName');
        MailPoet.Notice.success(
          (MailPoet.I18n.t('formDuplicated')).replace('%$1s', formName)
        );
        refresh();
      }).fail((response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map((error) => error.message),
            { scroll: true }
          );
        }
      });
    },
  },
  {
    name: 'trash',
  },
];

class FormList extends React.Component {
  goToSelectTemplate = () => {
    setTimeout(() => {
      window.location = window.mailpoet_form_template_selection_url;
    }, 200); // leave some time for the event to track
  };

  updateStatus = (e) => {
    // make the event persist so that we can still override the selected value
    // in the ajax callback
    e.persist();

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'forms',
      action: 'setStatus',
      data: {
        id: Number(e.target.getAttribute('data-id')),
        status: e.target.value,
      },
    }).done((response) => {
      if (response.data.status === 'enabled') {
        MailPoet.Notice.success(MailPoet.I18n.t('formActivated'));
      }
    }).fail((response) => {
      MailPoet.Notice.showApiErrorNotice(response);

      // reset value to actual newsletter's status
      e.target.value = response.status;
    });
  };

  renderStatus(form) {
    return (
      <div>
        <p>
          <select
            data-id={form.id}
            defaultValue={form.status}
            onChange={this.updateStatus}
          >
            <option value="enabled">{MailPoet.I18n.t('active')}</option>
            <option value="disabled">{MailPoet.I18n.t('inactive')}</option>
          </select>
        </p>
        <p>
          {MailPoet.I18n.t('signups')}
          {': '}
          {form.signups.toLocaleString()}
        </p>
      </div>
    );
  }

  renderItem = (form, actions) => {
    const rowClasses = classNames(
      'manage-column',
      'column-primary',
      'has-row-actions'
    );

    const segments = window.mailpoet_segments
      .filter((segment) => (jQuery.inArray(segment.id, form.segments) !== -1));

    const placement = getFormPlacement(form.settings);

    return (
      <div>
        <td className={rowClasses}>
          <strong>
            <a
              className="row-title"
              href={`admin.php?page=mailpoet-form-editor&id=${form.id}`}
            >
              { form.name ? form.name : `(${MailPoet.I18n.t('noName')})`}
            </a>
          </strong>
          { actions }
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('status')}>
          { this.renderStatus(form) }
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('segments')}>
          <Tags segments={segments}>
            {form.settings.segments_selected_by === 'user' && <span className="mailpoet-tags-prefix">{MailPoet.I18n.t('userChoice')}</span>}
          </Tags>
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('type')}>
          { placement }
        </td>
        <td className="column-date" data-colname={MailPoet.I18n.t('updatedAt')}>
          <abbr>{ MailPoet.Date.format(form.updated_at) }</abbr>
        </td>
      </div>
    );
  };

  render() {
    return (
      <div>
        <h1 className="title">
          {MailPoet.I18n.t('pageTitle')}
          {' '}
          <button
            className="page-title-action"
            onClick={() => this.goToSelectTemplate()}
            data-automation-id="create_new_form"
            type="button"
          >
            {MailPoet.I18n.t('new')}
          </button>
        </h1>

        <Listing
          limit={window.mailpoet_listing_per_page}
          location={this.props.location}
          params={this.props.match.params}
          messages={messages}
          search={false}
          endpoint="forms"
          onRenderItem={this.renderItem}
          columns={columns}
          bulk_actions={bulkActions}
          item_actions={itemActions}
        />
      </div>
    );
  }
}

FormList.propTypes = {
  location: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  match: PropTypes.shape({
    params: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  }).isRequired,
};

export default withNpsPoll(FormList);
