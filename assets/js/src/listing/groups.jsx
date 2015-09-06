define('groups', ['react', 'classnames'], function(React, classNames) {
  var ListingGroups = React.createClass({
    handleSelect: function(group) {
      return this.props.onSelectGroup(group);
    },
    render: function() {
      var count = this.props.groups.length;
      var groups = this.props.groups.map(function(group, index) {

        var classes = classNames(
          { 'current' : (group.name === this.props.selected) }
        );

        return (
          <li key={index}>
            <a
              href="javascript:;"
              className={classes}
              onClick={this.handleSelect.bind(this, group.name)} >
              {group.label}
              <span className="count">({ group.count })</span>
            </a>{(index < (count - 1)) ? ' | ' : ''}
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
});