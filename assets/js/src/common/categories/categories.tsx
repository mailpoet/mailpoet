import React from 'react';
import classNames from 'classnames';
import PropTypes from 'prop-types';

type Category = {
  name: string,
  label: string,
  count?: number | string,
};

type Props = {
  onSelect: (category: any) => any,
  categories: Category[],
  active: string,
};

const Categories = ({ onSelect, categories, active }: Props) => {
  const cats = categories.map((category) => {
    if (category.name === 'trash' && category.count === 0) {
      return false;
    }

    const classes = classNames(
      { 'mailpoet-categories-current': (category.name === active) }
    );

    return (
      <li key={category.name}>
        <a
          href="#"
          className={classes}
          onClick={(event) => {
            event.preventDefault();
            onSelect(category.name);
          }}
          data-automation-id={`filters_${category.label.replace(' ', '_').toLowerCase()}`}
        >
          <span className="mailpoet-categories-title">
            {category.label}
          </span>
          {category.count > 0 && (
            <>
              &nbsp;
              <span className="mailpoet-categories-count">
                { parseInt(category.count.toString(), 10).toLocaleString() }
              </span>
            </>
          )}
        </a>
      </li>
    );
  });

  return (
    <ul className="mailpoet-categories">
      { cats }
    </ul>
  );
};

Categories.propTypes = {
  onSelect: PropTypes.func.isRequired,
  categories: PropTypes.arrayOf(PropTypes.shape({
    name: PropTypes.string,
    label: PropTypes.string,
    count: PropTypes.number,
  })).isRequired,
  active: PropTypes.string.isRequired, //  eslint-disable-line react/forbid-prop-types
};

export default Categories;
