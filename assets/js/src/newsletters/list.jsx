define(
  [
    'react',
    'listing/listing.jsx',
    'classnames'
  ],
  function(
    React,
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

    var NewsletterList = React.createClass({
      renderItem: function(newsletter, actions) {
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
              { actions }
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
          <Listing
            endpoint="newsletters"
            onRenderItem={this.renderItem}
            items={this.getItems}
            columns={columns} />
        );
      }
    });

    return NewsletterList;
  }
);