import React from 'react';
import classNames from 'classnames';
import PropTypes from 'prop-types';

class ListingGroups extends React.Component {
  constructor(props) {
    super(props);
    this.handleSelect = this.handleSelect.bind(this);
  }

  handleSelect(group) {
    return this.props.onSelectGroup(group);
  }

  render() {
    const groups = this.props.groups.map((group, index) => {
      if (group.name === 'trash' && group.count === 0) {
        return false;
      }

      const classes = classNames(
          { current: (group.name === this.props.group) }
        );

      return (
        <li key={group.name}>
          {(index > 0) ? ' |' : ''}
          <a
            href="javascript:;"
            className={classes}
            onClick={() => this.handleSelect(group.name)}
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
  }
}

ListingGroups.propTypes = {
  onSelectGroup: PropTypes.func.isRequired,
  groups: PropTypes.arrayOf(PropTypes.shape({
    name: PropTypes.string,
    count: PropTypes.number,
  })).isRequired,
  group: PropTypes.any.isRequired, //  eslint-disable-line react/forbid-prop-types
};

export default ListingGroups;
