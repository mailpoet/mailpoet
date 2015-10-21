define(['react', 'classnames'], function(React, classNames) {

    var ListingGroups = React.createClass({
      handleSelect: function(group) {
        return this.props.onSelectGroup(group);
      },
      render: function() {
        var groups = this.props.groups.map(function(group, index) {
          if(group.name === 'trash' && group.count === 0) {
            return false;
          }

          var classes = classNames(
            { 'current' : (group.name === this.props.group) }
          );

          return (
            <li key={index}>
              {(index > 0) ? ' |' : ''}
              <a
                href="javascript:;"
                className={classes}
                onClick={this.handleSelect.bind(this, group.name)} >
                {group.label} <span className="count">({ group.count })</span>
              </a>
            </li>
          );
        }.bind(this));

        return (
          <ul className="subsubsub">
            { groups }
          </ul>
        );
      }
    });

    return ListingGroups;
  }
);