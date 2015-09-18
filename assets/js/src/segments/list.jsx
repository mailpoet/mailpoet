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
        name: 'name',
        label: 'Name',
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

    var bulk_actions = [
      {
        name: 'trash',
        label: 'Trash'
      }
    ];

    var SegmentList = React.createClass({
      renderItem: function(segment, actions) {
        var rowClasses = classNames(
          'manage-column',
          'column-primary',
          'has-row-actions'
        );

        return (
          <div>
            <td className={ rowClasses }>
              <strong>
                <a>{ segment.name }</a>
              </strong>
              { actions }
            </td>
            <td className="column-date" data-colname="Subscribed on">
              <abbr>{ segment.created_at }</abbr>
            </td>
            <td className="column-date" data-colname="Last modified on">
              <abbr>{ segment.updated_at }</abbr>
            </td>
          </div>
        );
      },
      render: function() {
        return (
          <Listing
            endpoint="segments"
            onRenderItem={this.renderItem}
            columns={columns}
            bulk_actions={ bulk_actions } />
        );
      }
    });

    return SegmentList;
  }
);