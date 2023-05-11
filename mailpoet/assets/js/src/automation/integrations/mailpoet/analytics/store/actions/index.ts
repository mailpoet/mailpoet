import { select } from '@wordpress/data';
import { Query, Section } from '../types';
import { storeName } from '../constants';
import { updateSection } from '../../api';

export function setQuery(query: Query) {
  const sections = select(storeName).getSections();
  sections.forEach((section: Section) => {
    void updateSection(section, query);
  });
  return {
    type: 'SET_QUERY',
    payload: query,
  };
}

export function setSectionData(payload: Section) {
  return {
    type: 'SET_SECTION_DATA',
    payload,
  };
}
