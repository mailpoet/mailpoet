define(
  'list',
  [
    'react',
    'jquery',
    'mailpoet',
    'listing/listing.jsx',
    'classnames'
  ],
  function(
    React,
    jQuery,
    MailPoet,
    Listing,
    classNames
  ) {

    var columns = [
      {
        name: 'subject',
        label: 'Subject',
        sortable: true
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

    var actions = [
    ];

    var List = React.createClass({
      getItems: function(listing) {
        MailPoet.Ajax.post({
          endpoint: 'newsletters',
          action: 'get',
          data: {
            offset: (listing.state.page - 1) * listing.state.limit,
            limit: listing.state.limit,
            group: listing.state.group,
            search: listing.state.search,
            sort_by: listing.state.sort_by,
            sort_order: listing.state.sort_order
          },
          onSuccess: function(response) {
            if(listing.isMounted()) {
              listing.setState({
                items: response.items || [],
                filters: response.filters || [],
                groups: response.groups || [],
                count: response.count || 0,
                loading: false
              });
            }
          }.bind(listing)
        });
      },
      renderItem: function(newsletter) {
        var rowClasses = classNames(
          'manage-column',
          'column-primary',
          'has-row-actions'
        );

        return (
          <div>
            <td className={ rowClasses }>
              <strong>
                <a>{ newsletter.subject }</a>
              </strong>
            </td>
            <td className="column-date">
              <abbr>{ newsletter.created_at }</abbr>
            </td>
            <td className="column-date">
              <abbr>{ newsletter.updated_at }</abbr>
            </td>
          </div>
        );
      },
      render: function() {
        return (
          <Listing
            onRenderItem={this.renderItem}
            items={this.getItems}
            columns={columns}
            actions={actions} />
        );
      }
    });

    return List;
  }
);