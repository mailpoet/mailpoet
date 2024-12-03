import { Button } from '@wordpress/components';

export function TemplateCategoriesListSidebar( {
                                                 selectedCategory,
                                                 templateCategories,
                                                 onClickCategory,
                                               } ) {
  const baseClassName = 'block-editor-block-patterns-explorer__sidebar';
  return (
    <div className={baseClassName}>
      <div className={`${baseClassName}__categories-list`}>
        {templateCategories.map(({ name, label }) => {
          return (
            <Button
              key={name}
              label={label}
              className={`${baseClassName}__categories-list__item`}
              isPressed={selectedCategory === name}
              onClick={() => {
                onClickCategory(name);
              }}
            >
              {label}
            </Button>
          );
        })}
      </div>
    </div>
  );
}
