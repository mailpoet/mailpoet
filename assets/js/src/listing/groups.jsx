define(['react', 'classnames'], (React, classNames) => {
  const ListingGroups = React.createClass({
    handleSelect: function (group) {
      return this.props.onSelectGroup(group);
    },
    render: function () {
      const groups = this.props.groups.map((group, index) => {
        if (group.name === 'trash' && group.count === 0) {
          return false;
        }

        const classes = classNames(
            { current: (group.name === this.props.group) }
          );

        return (
          <li key={index}>
            {(index > 0) ? ' |' : ''}
            <a
              href="javascript:;"
              className={classes}
              onClick={this.handleSelect.bind(this, group.name)}
            >
              {group.label}
              <span className="count">({ parseInt(group.count, 10).toLocaleString() })</span>
            </a>
          </li>
        );
      });

      return (
        <ul className="subsubsub">
          { groups }
        </ul>
      );
    },
  });

  return ListingGroups;
}
);
