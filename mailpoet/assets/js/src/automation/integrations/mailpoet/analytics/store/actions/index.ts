import { dispatch, select } from '@wordpress/data';
import { getCurrentDates } from '@woocommerce/date';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { apiFetch } from '@wordpress/data-controls';
import { Hooks } from 'wp-js-hooks';
import { CurrentView, Query, Section, SectionData } from '../types';
import { storeName } from '../constants';
import { getSampleData } from '../samples';
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

export function updateCurrentView(sectionId: string, currentView: CurrentView) {
  const currentSection = select(storeName).getSection(sectionId);
  const payload = {
    ...currentSection,
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
  void dispatch(storeName).resetSectionData(section);

  const sampleData = Hooks.applyFilters(
    'mailpoet_analytics_section_sample_data',
    getSampleData(section.id),
    section.id,
  ) as SectionData;

  if (sampleData) {
    return {
      type: 'SET_SECTION_DATA',
      payload: { ...section, data: sampleData },
    };
  }

  const formatDate = (date: Date, endOfDay = false): string => {
    const newDate = new Date(date.getTime());
    if (endOfDay) {
      newDate.setUTCHours(23, 59, 59, 999);
    } else {
      newDate.setUTCHours(0, 0, 0, 0);
    }
    return newDate.toISOString();
  };

  const query = queryParam ?? select(storeName).getCurrentQuery();
  const defaultDateRange = 'period=month&compare=previous_year';
  const { primary: primaryDate, secondary: secondaryDate } = getCurrentDates(
    query,
    defaultDateRange,
  );

  const dates = {
    primary: {
      after: formatDate(primaryDate.after.toDate()),
      before: formatDate(primaryDate.before.toDate(), true),
    },
    ...(section.withPreviousData
      ? {
          secondary: {
            after: formatDate(secondaryDate.after.toDate()),
            before: formatDate(secondaryDate.before.toDate(), true),
          },
        }
      : {}),
  };

  const id = select(editorStoreName).getAutomationData().id;
  const customQuery = section.customQuery ?? {};
  const args = { id, query: { ...dates, ...customQuery } };

  const response: { data: SectionData } = yield apiFetch({
    path: addQueryArgs(section.endpoint, args),
    method: 'GET',
  });

  if (section?.updateCallback) {
    section.updateCallback(response?.data);
  }

  return {
    type: 'SET_SECTION_DATA',
    payload: { ...section, data: response?.data },
  };
}

export function openPremiumModal(content: JSX.Element, utmCampaign?: string) {
  return {
    type: 'OPEN_PREMIUM_MODAL',
    content,
    utmCampaign,
  };
}

export function openPremiumModalForSampleData() {
  return {
    type: 'OPEN_PREMIUM_MODAL',
    content: __("You're viewing sample data.", 'mailpoet'),
    utmCampaign: 'automation_analytics_sample_data',
  };
}

export function closePremiumModal() {
  return {
    type: 'CLOSE_PREMIUM_MODAL',
  };
}
