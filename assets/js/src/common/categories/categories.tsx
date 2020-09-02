import React from 'react';
import classNames from 'classnames';
import CategoriesItem, { Category } from './categories_item';

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
