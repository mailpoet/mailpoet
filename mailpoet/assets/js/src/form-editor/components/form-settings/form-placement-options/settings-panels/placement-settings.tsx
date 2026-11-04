import { MailPoet } from 'mailpoet';
import { useDispatch, useSelect } from '@wordpress/data';
import { ToggleControl } from '@wordpress/components';
import { assocPath, compose, cond, identity, isEqual, sortBy } from 'lodash/fp';
import { withBoundary } from 'common';
import { ItemSelectControl } from '../../item-select-control';
import { storeName } from '../../../../store';
import { FormSettingsType } from '../../../../store/form-data-types';

type Props = {
  settingsPlacementKey: string;
};

function PlacementSettings({ settingsPlacementKey }: Props): JSX.Element {
  const formSettings = useSelect(
    (select) => select(storeName).getFormSettings(),
    [],
  );
  const tags = useSelect(
    (select) =>
      sortBy(
        'name',
        select(storeName)
          .getAllWPTags()
          .concat(select(storeName).getAllWooCommerceTags()),
      ),
    [],
  );
  const categories = useSelect(
    (select) =>
      sortBy(
        'name',
        select(storeName)
          .getAllWPCategories()
          .concat(select(storeName).getAllWooCommerceCategories()),
      ),
    [],
  );
  const pages = useSelect((select) => select(storeName).getAllWPPages(), []);
  const posts = useSelect(
    (select) =>
      sortBy(
        'name',
        select(storeName)
          .getAllWPPosts()
          .concat(select(storeName).getAllWooCommerceProducts()),
      ),
    [],
  );
  const { changeFormSettings } = useDispatch(storeName);

  const placement: FormSettingsType['formPlacement']['belowPosts'] =
    formSettings.formPlacement[settingsPlacementKey];
  const placementPath = `formPlacement.${settingsPlacementKey}`;

  // Selecting specific items turns off the related "All ..." toggles.
  const changeSelected = (
    key: string,
    current: (string | number)[],
    selected: string[],
    allKeys: string[],
  ): void => {
    if (isEqual(current, selected)) {
      return;
    }
    let newSettings = assocPath(
      `${placementPath}.${key}`,
      selected,
    )(formSettings);
    if (selected.length) {
      allKeys.forEach((allKey) => {
        newSettings = assocPath(
          `${placementPath}.${allKey}`,
          false,
        )(newSettings);
      });
    }
    changeFormSettings(newSettings);
  };

  const placementSupportsArchives = settingsPlacementKey !== 'belowPosts';

  return (
    <>
      {placementSupportsArchives && (
        <ToggleControl
          label={MailPoet.I18n.t('placeFormOnHomepage')}
          checked={formSettings.formPlacement[settingsPlacementKey].homepage}
          onChange={(newValue): void => {
            compose([
              changeFormSettings,
              assocPath(
                `formPlacement.${settingsPlacementKey}.homepage`,
                newValue,
              ),
            ])(formSettings);
          }}
        />
      )}
      <ToggleControl
        label={MailPoet.I18n.t('placeFormOnAllPages')}
        checked={formSettings.formPlacement[settingsPlacementKey].pages.all}
        onChange={(newValue): void => {
          compose([
            changeFormSettings,
            assocPath(
              `formPlacement.${settingsPlacementKey}.pages.all`,
              newValue,
            ),
            cond([
              [
                // condition, if the predicate function is true the next compose is run
                (): boolean => newValue,
                compose([
                  assocPath(
                    `formPlacement.${settingsPlacementKey}.pages.selected`,
                    [],
                  ), // if enabled clear selected pages
                  assocPath(
                    `formPlacement.${settingsPlacementKey}.categories`,
                    [],
                  ), // if enabled clear selected categories
                  assocPath(`formPlacement.${settingsPlacementKey}.tags`, []), // if enabled clear selected tags
                ]),
              ],
              [(): boolean => !newValue, identity], // if disabled do nothing
            ]),
          ])(formSettings);
        }}
      />
      <div data-automation-id="form-placement-select-page">
        <div className="form-editor-placement-selection">
          <ItemSelectControl
            label={MailPoet.I18n.t('displayOnPages')}
            hideLabelFromVision
            placeholder={MailPoet.I18n.t('selectPage')}
            items={pages}
            selected={placement.pages.selected}
            onChange={(selected): void =>
              changeSelected(
                'pages.selected',
                placement.pages.selected,
                selected,
                ['pages.all'],
              )
            }
          />
        </div>
      </div>
      <ToggleControl
        label={MailPoet.I18n.t('placeFormOnAllPosts')}
        checked={formSettings.formPlacement[settingsPlacementKey].posts.all}
        onChange={(newValue): void => {
          compose([
            changeFormSettings,
            assocPath(
              `formPlacement.${settingsPlacementKey}.posts.all`,
              newValue,
            ),
            cond([
              [
                (): boolean => newValue,
                compose([
                  assocPath(
                    `formPlacement.${settingsPlacementKey}.posts.selected`,
                    [],
                  ), // if enabled clear selected pages
                  assocPath(
                    `formPlacement.${settingsPlacementKey}.categories`,
                    [],
                  ), // if enabled clear selected categories
                  assocPath(`formPlacement.${settingsPlacementKey}.tags`, []), // if enabled clear selected tags
                ]),
              ],
              [(): boolean => !newValue, identity], // if disabled do nothing
            ]),
          ])(formSettings);
        }}
      />
      <div className="form-editor-placement-selection">
        <ItemSelectControl
          label={MailPoet.I18n.t('displayOnPosts')}
          hideLabelFromVision
          placeholder={MailPoet.I18n.t('selectPage')}
          items={posts}
          selected={placement.posts.selected}
          onChange={(selected): void =>
            changeSelected(
              'posts.selected',
              placement.posts.selected,
              selected,
              ['posts.all'],
            )
          }
        />
      </div>
      <div className="form-editor-placement-selection">
        <ItemSelectControl
          label={MailPoet.I18n.t('displayOnCategories')}
          placeholder={MailPoet.I18n.t('selectPage')}
          items={categories}
          selected={placement.categories}
          onChange={(selected): void =>
            changeSelected('categories', placement.categories, selected, [
              'pages.all',
              'posts.all',
            ])
          }
        />
      </div>
      <div className="form-editor-placement-selection">
        <ItemSelectControl
          label={MailPoet.I18n.t('displayOnTags')}
          placeholder={MailPoet.I18n.t('selectPage')}
          items={tags}
          selected={placement.tags}
          onChange={(selected): void =>
            changeSelected('tags', placement.tags, selected, [
              'pages.all',
              'posts.all',
            ])
          }
        />
      </div>
      {placementSupportsArchives && (
        <>
          <ToggleControl
            label={MailPoet.I18n.t('displayOnAllTagArchives')}
            checked={
              formSettings.formPlacement[settingsPlacementKey].tagArchives.all
            }
            onChange={(newValue): void => {
              compose([
                changeFormSettings,
                assocPath(
                  `formPlacement.${settingsPlacementKey}.tagArchives.all`,
                  newValue,
                ),
                cond([
                  [
                    // condition, if the predicate function is true the next compose is run
                    (): boolean => newValue,
                    compose([
                      assocPath(
                        `formPlacement.${settingsPlacementKey}.tagArchives.selected`,
                        [],
                      ), // if enabled clear selected tags
                    ]),
                  ],
                  [(): boolean => !newValue, identity], // if disabled do nothing
                ]),
              ])(formSettings);
            }}
          />
          <div data-automation-id="form-placement-select-archive-tags">
            <div className="form-editor-placement-selection">
              <ItemSelectControl
                label={MailPoet.I18n.t('selectSpecificArchiveTags')}
                hideLabelFromVision
                placeholder={MailPoet.I18n.t('selectSpecificArchiveTags')}
                items={tags}
                selected={placement.tagArchives.selected}
                onChange={(selected): void =>
                  changeSelected(
                    'tagArchives.selected',
                    placement.tagArchives.selected,
                    selected,
                    ['tagArchives.all'],
                  )
                }
              />
            </div>
          </div>
          <ToggleControl
            label={MailPoet.I18n.t('displayOnAllCategoryArchives')}
            checked={
              formSettings.formPlacement[settingsPlacementKey].categoryArchives
                .all
            }
            onChange={(newValue): void => {
              compose([
                changeFormSettings,
                assocPath(
                  `formPlacement.${settingsPlacementKey}.categoryArchives.all`,
                  newValue,
                ),
                cond([
                  [
                    // condition, if the predicate function is true the next compose is run
                    (): boolean => newValue,
                    compose([
                      assocPath(
                        `formPlacement.${settingsPlacementKey}.categoryArchives.selected`,
                        [],
                      ), // if enabled clear selected categories
                    ]),
                  ],
                  [(): boolean => !newValue, identity], // if disabled do nothing
                ]),
              ])(formSettings);
            }}
          />
          <div data-automation-id="form-placement-select-archive-categories">
            <div className="form-editor-placement-selection">
              <ItemSelectControl
                label={MailPoet.I18n.t('selectSpecificArchiveCategories')}
                hideLabelFromVision
                placeholder={MailPoet.I18n.t('selectSpecificArchiveCategories')}
                items={categories}
                selected={placement.categoryArchives.selected}
                onChange={(selected): void =>
                  changeSelected(
                    'categoryArchives.selected',
                    placement.categoryArchives.selected,
                    selected,
                    ['categoryArchives.all'],
                  )
                }
              />
            </div>
          </div>
        </>
      )}
    </>
  );
}

PlacementSettings.displayName = 'FormEditorPlacementSettings';
const PlacementSettingsWithBoundary = withBoundary(PlacementSettings);
export { PlacementSettingsWithBoundary as PlacementSettings };
