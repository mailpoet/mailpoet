define(
  [
    'react',
    'react-router',
    'listing/listing.jsx',
    'classnames',
    'jquery'
  ],
  function(
    React,
    Router,
    Listing,
    classNames,
    jQuery
  ) {
    var Link = Router.Link;

    var columns = [
      {
        name: 'subject',
        label: 'Subject',
        sortable: true
      },
      {
        name: 'status',
        label: 'Status'
      },
      {
        name: 'segments',
        label: 'Lists'
      },
      {
        name: 'created_at',
        label: 'Created on',
        sortable: true
      },
      {
        name: 'updated_at',
        label: 'Last modified on',
        sortable: true
      }
    ];

    var messages = {
      onTrash: function(response) {
        var count = ~~response;
        var message = null;

        if(count === 1) {
          message = (
            '1 newsletter was moved to the trash.'
          );
        } else {
          message = (
            '%$1d newsletters were moved to the trash.'
          ).replace('%$1d', count);
        }
        MailPoet.Notice.success(message);
      },
      onDelete: function(response) {
        var count = ~~response;
        var message = null;

        if(count === 1) {
          message = (
            '1 newsletter was permanently deleted.'
          );
        } else {
          message = (
            '%$1d newsletters were permanently deleted.'
          ).replace('%$1d', count);
        }
        MailPoet.Notice.success(message);
      },
      onRestore: function(response) {
        var count = ~~response;
        var message = null;

        if(count === 1) {
          message = (
            '1 newsletter has been restored from the trash.'
          );
        } else {
          message = (
            '%$1d newsletters have been restored from the trash.'
          ).replace('%$1d', count);
        }
        MailPoet.Notice.success(message);
      }
    };

    var bulk_actions = [
      {
        name: 'trash',
        label: 'Trash',
        onSuccess: messages.onTrash
      }
    ];

    var item_actions = [
      {
        name: 'edit',
        link: function(item) {
          return (
            <a href={ `?page=mailpoet-newsletter-editor&id=${ item.id }` }>
              Edit
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
        if(item.queue === null) {
          return (
            <span>Not sent yet.</span>
          );
        } else {
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
                Sent to {
                  item.queue.count_processed - item.queue.count_failed
                } out of { item.queue.count_total }.
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
                >Resume</a>
                <a
                  id={ 'pause_'+item.id }
                  className="button mailpoet_pause"
                  style={{ display: (item.queue.status === null) ? 'inline-block': 'none' }}
                  href="javascript:;"
                  onClick={ this.pauseSending.bind(null, item) }
                >Pause</a>
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
      renderItem: function(newsletter, actions) {
        var rowClasses = classNames(
          'manage-column',
          'column-primary',
          'has-row-actions'
        );

        var segments = mailpoet_segments.filter(function(segment) {
          return (jQuery.inArray(segment.id, newsletter.segments) !== -1);
        }).map(function(segment) {
          return segment.name;
        }).join(', ');

        return (
          <div>
            <td className={ rowClasses }>
              <strong>
                <a>{ newsletter.subject }</a>
              </strong>
              { actions }
            </td>
            <td className="column" data-colname="Lists">
              { this.renderStatus(newsletter) }
            </td>
            <td className="column" data-colname="Lists">
              { segments }
            </td>
            <td className="column-date" data-colname="Subscribed on">
              <abbr>{ newsletter.created_at }</abbr>
            </td>
            <td className="column-date" data-colname="Last modified on">
              <abbr>{ newsletter.updated_at }</abbr>
            </td>
          </div>
        );
      },
      render: function() {
        return (
          <div>
            <h2 className="title">
              Newsletters <Link className="add-new-h2" to="/new">New</Link>
            </h2>

            <Listing
              params={ this.props.params }
              endpoint="newsletters"
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