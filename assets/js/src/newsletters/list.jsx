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
        var count = ~~response.newsletters;
        var message = null;

        if(count === 1 || response === true) {
          message = (
            '1 newsletter was moved to the trash.'
          );
        } else if(count > 1) {
          message = (
            '%$1d newsletters were moved to the trash.'
          ).replace('%$1d', count);
        }

        if(message !== null) {
          MailPoet.Notice.success(message);
        }
      },
      onDelete: function(response) {
        var count = ~~response.newsletters;
        var message = null;

        if(count === 1 || response === true) {
          message = (
            '1 newsletter was permanently deleted.'
          );
        } else if(count > 1) {
          message = (
            '%$1d newsletters were permanently deleted.'
          ).replace('%$1d', count);
        }

        if(message !== null) {
          MailPoet.Notice.success(message);
        }
      },
      onRestore: function(response) {
        var count = ~~response.newsletters;
        var message = null;

        if(count === 1 || response === true) {
          message = (
            '1 newsletter has been restored from the trash.'
          );
        } else if(count > 1) {
          message = (
            '%$1d newsletters have been restored from the trash.'
          ).replace('%$1d', count);
        }

        if(message !== null) {
          MailPoet.Notice.success(message);
        }
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
              messages={ messages } />
          </div>
        );
      }
    });

    return NewsletterList;
  }
);