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
    const groups = this.props.groups.map((group) => {
      if (group.name === 'trash' && group.count === 0) {
        return false;
      }

      const classes = classNames(
        { 'mailpoet-listing-groups-current': (group.name === this.props.group) }
      );

      return (
        <li key={group.name}>
          <a
            href="#"
            className={classes}
            onClick={(event) => {
              event.preventDefault();
              this.handleSelect(group.name);
            }}
            data-automation-id={`filters_${group.label.replace(' ', '_').toLowerCase()}`}
          >
            <span className="mailpoet-listing-groups-title">
              {group.label}
            </span>
            {group.count > 0 && (
              <>
                &nbsp;
                <span className="mailpoet-listing-groups-count">
                  { parseInt(group.count, 10).toLocaleString() }
                </span>
              </>
            )}
          </a>
        </li>
      );
    });

    return (
      <ul className="mailpoet-listing-groups">
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
