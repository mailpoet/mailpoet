import { noop } from 'lodash';
import Categories from '../categories';

export default {
  title: 'Categories',
  component: Categories,
};

export function CategoriesWithCount() {
  const categories = [
    { name: 'all', label: 'All', count: 35 },
    { name: 'draft', label: 'Draft' },
    { name: 'scheduled', label: 'Scheduled' },
    { name: 'sent', label: 'Sent', count: 12 },
    { name: 'trash', label: 'Trash', count: 6 },
  ];

  return (
    <>
      <Categories
        onSelect={noop}
        categories={categories}
        active={categories[0].name}
      />
      <Categories
        onSelect={noop}
        categories={categories}
        active={categories[2].name}
      />
      <Categories
        onSelect={noop}
        categories={categories}
        active={categories[4].name}
      />
    </>
  );
}

export function CategoriesWithoutCount() {
  const categories = [
    { name: 'newsletters', label: 'Newsletters' },
    { name: 'welcome', label: 'Welcome Emails' },
    { name: 'notifications', label: 'Post Notifications' },
    { name: 'all', label: 'All' },
    { name: 'sent', label: 'Recently sent' },
  ];

  return (
    <>
      <Categories
        onSelect={noop}
        categories={categories}
        active={categories[0].name}
      />
      <Categories
        onSelect={noop}
        categories={categories}
        active={categories[2].name}
      />
      <Categories
        onSelect={noop}
        categories={categories}
        active={categories[4].name}
      />
    </>
  );
}
