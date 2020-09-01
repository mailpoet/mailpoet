import React from 'react';
import classNames from 'classnames';
import CategoriesItem from './categories_item';

type Category = {
  name: string,
  label: string,
  count?: number | string,
  automationId?: string,
};

type Props = {
  onSelect: (name: string) => any,
  categories: Category[],
  active: string,
};

const Categories = ({ onSelect, categories, active }: Props) => {
  const cats = categories.map((category) => (
    <CategoriesItem
      {...category}
      key={category.name}
      onSelect={onSelect}
      active={category.name === active}
    />
  ));

  return (
    <ul className="mailpoet-categories">
      { cats }
    </ul>
  );
};

export default Categories;
