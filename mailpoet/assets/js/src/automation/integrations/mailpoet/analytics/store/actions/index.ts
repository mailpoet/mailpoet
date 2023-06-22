import { dispatch, select } from '@wordpress/data';
import { getCurrentDates } from '@woocommerce/date';
import { addQueryArgs } from '@wordpress/url';
import { apiFetch } from '@wordpress/data-controls';
import { Query, Section, SectionData } from '../types';
import { storeName } from '../constants';

export function setQuery(query: Query) {
  const sections = select(storeName).getSections();
  sections.forEach((section: Section) => {
    void dispatch(storeName).updateSection(section, query);
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

export function resetSectionData(section: Section) {
  const payload = {
    ...section,
    data: undefined,
  };
  return {
    type: 'SET_SECTION_DATA',
    payload,
  };
}

export function* updateSection(
  section: Section,
  queryParam: Query | undefined = undefined,
) {
  dispatch(storeName).resetSectionData(section);
  const query = queryParam ?? select(storeName).getCurrentQuery();
  const defaultDateRange = 'period=month&compare=previous_year';

  const { primary: primaryDate, secondary: secondaryDate } = getCurrentDates(
    query,
    defaultDateRange,
  );

  const dates = section.withPreviousData
    ? {
        primary: {
          after: primaryDate.after.toDate().toISOString(),
          before: primaryDate.before.toDate().toISOString(),
        },
        secondary: {
          after: secondaryDate.after.toDate().toISOString(),
          before: secondaryDate.before.toDate().toISOString(),
        },
      }
    : {
        primary: {
          after: primaryDate.after.toDate().toISOString(),
          before: primaryDate.before.toDate().toISOString(),
        },
      };
  const id = select(storeName).getAutomation().id;

  const customQuery = section.customQuery ?? {};

  const args = { id, query: { ...dates, ...customQuery } };
  const path = addQueryArgs(section.endpoint, args);
  const method = 'GET';
  const response: {
    data: SectionData;
  } = yield apiFetch({
    path,
    method,
  });

  const payload = {
    ...section,
    data: response?.data || undefined,
  };
  return {
    type: 'SET_SECTION_DATA',
    payload,
  };
}
