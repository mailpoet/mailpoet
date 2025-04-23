import classnames from 'classnames';
import { Component } from 'react';
import jQuery from 'jquery';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { escapeHTML } from '@wordpress/escape-html';
import { useLocation, useParams } from 'react-router-dom';

import { Button } from 'common';
import { Listing } from 'listing/listing.jsx';
import { MailPoet } from 'mailpoet';
import { SegmentTags } from 'common/tag/tags';
import { Toggle } from 'common/form/toggle/toggle';
import { withNpsPoll } from 'nps-poll.jsx';
import { FormsHeading, onAddNewForm } from './heading';

const columns = [
  {
    name: 'name',
    label: __('Name', 'mailpoet'),
    sortable: true,
  },
  {
    name: 'segments',
    label: __('Lists', 'mailpoet'),
  },
  {
    name: 'type',
    label: __('Type', 'mailpoet'),
  },
  {
    name: 'status',
    label: __('Status', 'mailpoet'),
  },
  {
    name: 'updated_at',
    label: __('Modified date', 'mailpoet'),
    sortable: true,
  },
];

const messages = {
  onTrash: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = __('1 form was moved to the trash.', 'mailpoet');
    } else {
      message = __('%1$d forms were moved to the trash.', 'mailpoet').replace(
        '%1$d',
        count,
      );
    }
    MailPoet.Notice.success(message);
  },
  onDelete: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = __('1 form was permanently deleted.', 'mailpoet');
    } else {
      message = __('%1$d forms were permanently deleted.', 'mailpoet').replace(
        '%1$d',
        count,
      );
    }
    MailPoet.Notice.success(message);
  },
  onRestore: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = __('1 form has been restored from the trash.', 'mailpoet');
    } else {
      message = __(
        '%1$d forms have been restored from the trash.',
        'mailpoet',
      ).replace('%1$d', count);
    }
    MailPoet.Notice.success(message);
  },
  onNoItemsFound: () => (
    <div className="mailpoet-forms-add-new-row">
      <p>{__('No forms were found. Why not create a new one?', 'mailpoet')}</p>
      <Button onClick={onAddNewForm} automationId="add_new_form">
        {__('Add new form', 'mailpoet')}
      </Button>
    </div>
  ),
};

const bulkActions = [
  {
    name: 'trash',
    label: __('Move to trash', 'mailpoet'),
    onSuccess: messages.onTrash,
  },
];

function getFormPlacement(settings) {
  const placements = [];
  /* eslint-disable camelcase */
  if (settings?.form_placement?.fixed_bar?.enabled === '1') {
    // translators: This is a text on a widget that leads to settings for form placement - form type is fixed bar
    placements.push(__('Fixed bar', 'mailpoet'));
  }
  if (settings?.form_placement?.below_posts?.enabled === '1') {
    // translators: This is a text on a widget that leads to settings for form placement
    placements.push(__('Below pages', 'mailpoet'));
  }
  if (settings?.form_placement?.popup?.enabled === '1') {
    // translators: This is a text on a widget that leads to settings for form placement - form type is pop-up, it will be displayed on page in a small modal window
    placements.push(__('Pop-up', 'mailpoet'));
  }
  if (settings?.form_placement?.slide_in?.enabled === '1') {
    // translators: This is a text on a widget that leads to settings for form placement - form type is slide in
    placements.push(__('Slide–in', 'mailpoet'));
  }
  if (placements.length > 0) {
    return placements.join(', ');
  }
  /* eslint-enable camelcase */
  // translators: Placement of the form using theme widget
  return __('Others (widget)', 'mailpoet');
}

const itemActions = [
  {
    name: 'edit',
    className: 'mailpoet-hide-on-mobile',
    label: __('Edit', 'mailpoet'),
    link: function link(item) {
      return (
        <a href={`admin.php?page=mailpoet-form-editor&id=${item.id}`}>
          {__('Edit', 'mailpoet')}
        </a>
      );
    },
  },
  {
    name: 'duplicate',
    className: 'mailpoet-hide-on-mobile',
    label: __('Duplicate', 'mailpoet'),
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
            : // translators: Fallback for forms without a name in a form list
              __('no name', 'mailpoet');
          MailPoet.Notice.success(
            __('Form "%1$s" has been duplicated.', 'mailpoet').replace(
              '%1$s',
              escapeHTML(formName),
            ),
          );
          refresh();
        })
        .fail((response) => {
          if (response.errors.length > 0) {
            MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
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
          MailPoet.Notice.success(
            __('Your Form is now activated!', 'mailpoet'),
          );
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
          {__('Sign-ups', 'mailpoet')}
          {': '}
          {form.signups.toLocaleString()}
        </p>
      </div>
    );
  }

  renderItem = (form, actions) => {
    if (form.settings === null) {
      MailPoet.Notice.error(
        __(
          'Form settings of "%1$s" form are corrupted. Please [link]reconfigure the form in the editor[/link].',
          'mailpoet',
        )
          .replace('%1$s', escapeHTML(form.name))
          .replace(
            '[link]',
            `<a class="mailpoet-link" href="admin.php?page=mailpoet-form-editor&id=${parseInt(
              form.id,
              10,
            )}">`,
          )
          .replace('[/link]', '</a>'),
      );
    }
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
            {form.name ? form.name : `(${__('no name', 'mailpoet')})`}
          </a>
          {actions}
        </td>
        <td className="column" data-colname={__('Lists', 'mailpoet')}>
          <SegmentTags segments={segments} dimension="large">
            {form.settings?.segments_selected_by === 'user' && (
              <span className="mailpoet-tags-prefix">
                {__('User choice:', 'mailpoet')}
              </span>
            )}
          </SegmentTags>
        </td>
        <td className="column" data-colname={__('Type', 'mailpoet')}>
          {placement}
        </td>
        <td className="column" data-colname={__('Status', 'mailpoet')}>
          {this.renderStatus(form)}
        </td>
        <td
          className="column-date mailpoet-hide-on-mobile"
          data-colname={__('Modified date', 'mailpoet')}
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
          params={this.props.params}
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
  params: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
};
FormListComponent.displayName = 'FormList';
const FormListWithPoll = withNpsPoll(FormListComponent);

export function FormList(props) {
  const location = useLocation();
  const params = useParams();
  return <FormListWithPoll {...props} location={location} params={params} />;
}
