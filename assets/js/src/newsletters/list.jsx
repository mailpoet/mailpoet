define(
  [
    'react',
    'react-router',
    'listing/listing.jsx',
    'classnames',
    'jquery',
    'mailpoet'
  ],
  function(
    React,
    Router,
    Listing,
    classNames,
    jQuery,
    MailPoet
  ) {
    var Link = Router.Link;

    var columns = [
      {
        name: 'subject',
        label: MailPoet.I18n.t('subject'),
        sortable: true
      },
      {
        name: 'status',
        label: MailPoet.I18n.t('status')
      },
      {
        name: 'segments',
        label: MailPoet.I18n.t('lists')
      },
      {
        name: 'statistics',
        label: MailPoet.I18n.t('statistics')
      },
      {
        name: 'created_at',
        label: MailPoet.I18n.t('createdOn'),
        sortable: true
      },
      {
        name: 'updated_at',
        label: MailPoet.I18n.t('lastModifiedOn'),
        sortable: true
      }
    ];

    var messages = {
      onTrash: function(response) {
        var count = ~~response;
        var message = null;

        if(count === 1) {
          message = (
            MailPoet.I18n.t('oneNewsletterTrashed')
          );
        } else {
          message = (
            MailPoet.I18n.t('multipleNewslettersTrashed')
          ).replace('%$1d', count);
        }
        MailPoet.Notice.success(message);
      },
      onDelete: function(response) {
        var count = ~~response;
        var message = null;

        if(count === 1) {
          message = (
            MailPoet.I18n.t('oneNewsletterDeleted')
          );
        } else {
          message = (
            MailPoet.I18n.t('multipleNewslettersDeleted')
          ).replace('%$1d', count);
        }
        MailPoet.Notice.success(message);
      },
      onRestore: function(response) {
        var count = ~~response;
        var message = null;

        if(count === 1) {
          message = (
            MailPoet.I18n.t('oneNewsletterRestored')
          );
        } else {
          message = (
            MailPoet.I18n.t('multipleNewslettersRestored')
          ).replace('%$1d', count);
        }
        MailPoet.Notice.success(message);
      }
    };

    var bulk_actions = [
      {
        name: 'trash',
        label: MailPoet.I18n.t('trash'),
        onSuccess: messages.onTrash
      }
    ];

    var item_actions = [
      {
        name: 'edit',
        link: function(item) {
          return (
            <a href={ `?page=mailpoet-newsletter-editor&id=${ item.id }` }>
              {MailPoet.I18n.t('edit')}
            </a>
          );
        }
      },
      {
        name: 'trash'
      }
    ];

    var NewsletterList = React.createClass({
      pauseSending: function(item) {
        MailPoet.Ajax.post({
          endpoint: 'sendingQueue',
          action: 'pause',
          data: item.id
        }).done(function() {
          jQuery('#resume_'+item.id).show();
          jQuery('#pause_'+item.id).hide();
        });
      },
      resumeSending: function(item) {
        MailPoet.Ajax.post({
          endpoint: 'sendingQueue',
          action: 'resume',
          data: item.id
        }).done(function() {
          jQuery('#pause_'+item.id).show();
          jQuery('#resume_'+item.id).hide();
        });
      },
      renderStatus: function(item) {
        if(!item.queue) {
          return (
            <span>{MailPoet.I18n.t('notSentYet')}</span>
          );
        } else {
          if (item.queue.status === 'scheduled') {
            return (
              <span>{MailPoet.I18n.t('scheduledFor')}  { MailPoet.Date.format(item.queue.scheduled_at) } </span>
            )
          }
          var progressClasses = classNames(
            'mailpoet_progress',
            { 'mailpoet_progress_complete': item.queue.status === 'completed'}
          );

          // calculate percentage done
          var percentage = Math.round(
            (item.queue.count_processed * 100) / (item.queue.count_total)
          );

          var label = false;

          if(item.queue.status === 'completed') {
            label = (
              <span>
                {
                  MailPoet.I18n.t('newsletterQueueCompleted')
                  .replace("%$1d", item.queue.count_processed - item.queue.count_failed)
                  .replace("%$2d", item.queue.count_total)
                }
              </span>
            );
          } else {
            label = (
              <span>
                { item.queue.count_processed } / { item.queue.count_total }
                &nbsp;&nbsp;
                <a
                  id={ 'resume_'+item.id }
                  className="button"
                  style={{ display: (item.queue.status === 'paused') ? 'inline-block': 'none' }}
                  href="javascript:;"
                  onClick={ this.resumeSending.bind(null, item) }
                >{MailPoet.I18n.t('resume')}</a>
                <a
                  id={ 'pause_'+item.id }
                  className="button mailpoet_pause"
                  style={{ display: (item.queue.status === null) ? 'inline-block': 'none' }}
                  href="javascript:;"
                  onClick={ this.pauseSending.bind(null, item) }
                >{MailPoet.I18n.t('pause')}</a>
              </span>
            );
          }

          return (
            <div>
              <div className={ progressClasses }>
                  <span
                    className="mailpoet_progress_bar"
                    style={ { width: percentage + "%"} }
                  ></span>
                  <span className="mailpoet_progress_label">
                    { percentage + "%" }
                  </span>
              </div>
              <p style={{ textAlign:'center' }}>
                { label }
              </p>
            </div>
          );
        }
      },
      renderStatistics: function(item) {
        if(!item.statistics || !item.queue || item.queue.count_processed == 0 || item.queue.status === 'scheduled') {
          return (
            <span>
              {MailPoet.I18n.t('notSentYet')}
            </span>
          );
        }

        var percentage_clicked = Math.round(
          (item.statistics.clicked * 100) / (item.queue.count_processed)
        );
        var percentage_opened = Math.round(
          (item.statistics.opened * 100) / (item.queue.count_processed)
        );
        var percentage_unsubscribed = Math.round(
          (item.statistics.unsubscribed * 100) / (item.queue.count_processed)
        );

        return (
          <span>
            { percentage_opened }%, { percentage_clicked }%, { percentage_unsubscribed }%
          </span>
        );
      },
      renderItem: function(newsletter, actions) {
        var rowClasses = classNames(
          'manage-column',
          'column-primary',
          'has-row-actions'
        );

        var segments = newsletter.segments.map(function(segment) {
          return segment.name
        }).join(', ');

        return (
          <div>
            <td className={ rowClasses }>
              <strong>
                <a>{ newsletter.subject }</a>
              </strong>
              { actions }
            </td>
            <td className="column" data-colname={ MailPoet.I18n.t('status') }>
              { this.renderStatus(newsletter) }
            </td>
            <td className="column" data-colname={ MailPoet.I18n.t('lists') }>
              { segments }
            </td>
            <td className="column" data-colname={ MailPoet.I18n.t('statistics') }>
              { this.renderStatistics(newsletter) }
            </td>
            <td className="column-date" data-colname={ MailPoet.I18n.t('createdOn') }>
              <abbr>{ MailPoet.Date.format(newsletter.created_at) }</abbr>
            </td>
            <td className="column-date" data-colname={ MailPoet.I18n.t('lastModifiedOn') }>
              <abbr>{ MailPoet.Date.format(newsletter.updated_at) }</abbr>
            </td>
          </div>
        );
      },
      render: function() {
        return (
          <div>
            <h1 className="title">
              {MailPoet.I18n.t('pageTitle')} <Link className="page-title-action" to="/new">{MailPoet.I18n.t('new')}</Link>
            </h1>

            <h2 className="nav-tab-wrapper">
              <Link to="/standard" className="nav-tab nav-tab-active">
                { MailPoet.I18n.t('tabStandardTitle') }
              </Link>
              <Link to="/welcome" className="nav-tab">
                { MailPoet.I18n.t('tabWelcomeTitle') }
              </Link>
              <Link to="/notification" className="nav-tab">
                { MailPoet.I18n.t('tabNotificationTitle') }
              </Link>
            </h2>

            <Listing
              limit={ mailpoet_listing_per_page }
              params={ this.props.params }
              endpoint="newsletters"
              tab="standard"
              onRenderItem={this.renderItem}
              columns={columns}
              bulk_actions={ bulk_actions }
              item_actions={ item_actions }
              messages={ messages }
              auto_refresh={ true } />

              <Listing
              limit={ mailpoet_listing_per_page }
              params={ this.props.params }
              endpoint="newsletters"
              tab="welcome"
              onRenderItem={this.renderItem}
              columns={columns}
              bulk_actions={ bulk_actions }
              item_actions={ item_actions }
              messages={ messages }
              auto_refresh={ true } />

              <Listing
              limit={ mailpoet_listing_per_page }
              params={ this.props.params }
              endpoint="newsletters"
              tab="notification"
              onRenderItem={this.renderItem}
              columns={columns}
              bulk_actions={ bulk_actions }
              item_actions={ item_actions }
              messages={ messages }
              auto_refresh={ true } />
          </div>
        );
      }
    });

    return NewsletterList;
  }
);
