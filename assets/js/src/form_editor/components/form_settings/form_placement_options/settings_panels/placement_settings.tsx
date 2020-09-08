import React from 'react';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import { ToggleControl } from '@wordpress/components';
import {
  assocPath,
  compose,
  __,
  cond,
  identity,
} from 'lodash/fp';
import Selection from 'form/fields/selection.jsx';

type Props = {
  settingsPlacementKey: string
}

const PlacementSettings = ({ settingsPlacementKey }: Props) => {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );
  const tags = useSelect(
    (select) => select('mailpoet-form-editor').getAllWPTags(),
    []
  );
  const categories = useSelect(
    (select) => select('mailpoet-form-editor').getAllWPCategories(),
    []
  );
  const pages = useSelect(
    (select) => select('mailpoet-form-editor').getAllWPPages(),
    []
  );
  const posts = useSelect(
    (select) => select('mailpoet-form-editor').getAllWPPosts(),
    []
  );
  const { changeFormSettings } = useDispatch('mailpoet-form-editor');

  return (
    <>
      <ToggleControl
        label={MailPoet.I18n.t('placeFormOnAllPages')}
        checked={formSettings.formPlacement[settingsPlacementKey].pages.all}
        onChange={(newValue) => {
          compose([
            changeFormSettings,
            assocPath(`formPlacement.${settingsPlacementKey}.pages.all`, newValue),
            cond([
              [
                () => newValue,
                compose([
                  assocPath(`formPlacement.${settingsPlacementKey}.pages.selected`, []), // if enabled clear selected pages
                  assocPath(`formPlacement.${settingsPlacementKey}.categories`, []), // if enabled clear selected categories
                  assocPath(`formPlacement.${settingsPlacementKey}.tags`, []), // if enabled clear selected tags
                ]),
              ],
              [() => !newValue, identity], // if disabled do nothing
            ]),
          ])(formSettings);
        }}
      />
      <Selection
        item={{
          id: formSettings.formPlacement[settingsPlacementKey].pages.selected.join(),
        }}
        onValueChange={(e) => compose([
          changeFormSettings,
          assocPath(`formPlacement.${settingsPlacementKey}.pages.selected`, e.target.value),
          assocPath(`formPlacement.${settingsPlacementKey}.pages.all`, false), // disable all if some pages are selected
        ])(formSettings)}
        field={{
          id: 'pages',
          name: 'pages',
          values: pages,
          multiple: true,
          placeholder: MailPoet.I18n.t('selectPage'),
          getLabel: (page) => page.name,
          selected: () => formSettings.formPlacement[settingsPlacementKey].pages.selected,
        }}
      />
      <ToggleControl
        label={MailPoet.I18n.t('placeFormOnAllPosts')}
        checked={formSettings.formPlacement[settingsPlacementKey].posts.all}
        onChange={(newValue) => {
          compose([
            changeFormSettings,
            assocPath(`formPlacement.${settingsPlacementKey}.posts.all`, newValue),
            cond([
              [
                () => newValue,
                compose([
                  assocPath(`formPlacement.${settingsPlacementKey}.posts.selected`, []), // if enabled clear selected pages
                  assocPath(`formPlacement.${settingsPlacementKey}.categories`, []), // if enabled clear selected categories
                  assocPath(`formPlacement.${settingsPlacementKey}.tags`, []), // if enabled clear selected tags
                ]),
              ],
              [() => !newValue, identity], // if disabled do nothing
            ]),
          ])(formSettings);
        }}
      />
      <Selection
        item={{
          id: formSettings.formPlacement[settingsPlacementKey].posts.selected.join(),
        }}
        onValueChange={(e) => compose([
          changeFormSettings,
          assocPath(`formPlacement.${settingsPlacementKey}.posts.selected`, e.target.value),
          assocPath(`formPlacement.${settingsPlacementKey}.posts.all`, false), // disable all if some posts are selected
        ])(formSettings)}
        field={{
          id: 'posts',
          name: 'posts',
          values: posts,
          multiple: true,
          placeholder: MailPoet.I18n.t('selectPage'),
          getLabel: (page) => page.name,
          selected: () => formSettings.formPlacement[settingsPlacementKey].posts.selected,
        }}
      />
      <h3>{MailPoet.I18n.t('displayOnCategories')}</h3>
      <Selection
        item={{
          id: formSettings.formPlacement[settingsPlacementKey].categories.join(),
        }}
        onValueChange={(e) => compose([
          changeFormSettings,
          assocPath(`formPlacement.${settingsPlacementKey}.categories`, e.target.value),
          assocPath(`formPlacement.${settingsPlacementKey}.pages.all`, false),
          assocPath(`formPlacement.${settingsPlacementKey}.posts.all`, false), // disable all if some posts are selected
        ])(formSettings)}
        field={{
          id: 'categories',
          name: 'categories',
          values: categories,
          multiple: true,
          placeholder: MailPoet.I18n.t('selectPage'),
          getLabel: (category) => category.name,
          selected: () => formSettings.formPlacement[settingsPlacementKey].categories,
        }}
      />
      <h3>{MailPoet.I18n.t('displayOnTags')}</h3>
      <Selection
        item={{
          id: formSettings.formPlacement[settingsPlacementKey].tags.join(),
        }}
        onValueChange={(e) => compose([
          changeFormSettings,
          assocPath(`formPlacement.${settingsPlacementKey}.tags`, e.target.value),
          assocPath(`formPlacement.${settingsPlacementKey}.pages.all`, false),
          assocPath(`formPlacement.${settingsPlacementKey}.posts.all`, false), // disable all if some posts are selected
        ])(formSettings)}
        field={{
          id: 'tags',
          name: 'tags',
          values: tags,
          multiple: true,
          placeholder: MailPoet.I18n.t('selectPage'),
          getLabel: (tag) => tag.name,
          selected: () => formSettings.formPlacement[settingsPlacementKey].tags,
        }}
      />
    </>
  );
};

export default PlacementSettings;
