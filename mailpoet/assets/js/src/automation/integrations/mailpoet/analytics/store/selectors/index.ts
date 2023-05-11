import { Query, Section, State } from '../types';

export function getCurrentQuery(state: State): Query {
  return state.query;
}

export function getSections(state: State): Section[] {
  return Object.values(state.sections);
}

export function getSection(state: State, id: string): Section | undefined {
  return state.sections[id] ?? undefined;
}

export function getAutomation(state: State) {
  return state.automation;
}
