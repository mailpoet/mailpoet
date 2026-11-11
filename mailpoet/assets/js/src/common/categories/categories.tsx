import classnames from 'classnames';
import { CategoriesItem, Category } from './categories-item';

type Props = {
  onSelect: (name: string) => void;
  categories: Category[];
  active: string;
  className?: string;
};

function Categories({ onSelect, categories, active, className }: Props) {
  const cats = categories.map((category) => (
    <CategoriesItem
      {...category}
      key={category.name}
      onSelect={onSelect}
      active={category.name === active}
    />
  ));

  return (
    <div className={classnames('mailpoet-categories', className)}>
      <div className="components-tab-panel__tabs">{cats}</div>
    </div>
  );
}

Categories.displayName = 'Categories';
export { Categories };
