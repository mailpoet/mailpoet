import { dispatch, select } from '@wordpress/data';
import { getCurrentDates } from '@woocommerce/date';
import { addQueryArgs } from '@wordpress/url';
import { apiFetch } from '@wordpress/data-controls';
import { CurrentView, Query, Section, SectionData } from '../types';
import { storeName } from '../constants';
import { storeName as editorStoreName } from '../../../../../editor/store/constants';

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

export function updateCurrentView(section: Section, currentView: CurrentView) {
  const payload = {
    ...section,
    currentView,
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

  const formatDate = (date: Date, endOfDay = false): string => {
    const dateString = `${date.getFullYear()}-${
      date.getMonth() < 9 ? '0' : ''
    }${date.getMonth() + 1}-${date.getDate() < 10 ? '0' : ''}${date.getDate()}`;
    const newDate = new Date(
      `${dateString}T${endOfDay ? '23:59:59.999' : '00:00:00.000'}Z`,
    );
    return newDate.toISOString();
  };

  const dates = section.withPreviousData
    ? {
        primary: {
          after: formatDate(primaryDate.after.toDate()),
          before: formatDate(primaryDate.before.toDate(), true),
        },
        secondary: {
          after: formatDate(secondaryDate.after.toDate()),
          before: formatDate(secondaryDate.before.toDate(), true),
        },
      }
    : {
        primary: {
          after: formatDate(primaryDate.after.toDate()),
          before: formatDate(primaryDate.before.toDate(), true),
        },
      };
  const id = select(editorStoreName).getAutomationData().id;

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
  if (section?.updateCallback) {
    section.updateCallback(response?.data);
  }
  return {
    type: 'SET_SECTION_DATA',
    payload,
  };
}
