import { select } from '@wordpress/data';
import { initializeApi, updateSection } from '../api';
import { Section, storeName } from '../store';

export function boot() {
  initializeApi();
  select(storeName)
    .getSections()
    .forEach((section: Section) => {
      void updateSection(section);
    });
}
