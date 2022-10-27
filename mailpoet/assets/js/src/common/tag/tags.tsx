import { ReactNode } from 'react';
import { Tag, TagVariant } from './tag';
import { Tooltip } from '../tooltip/tooltip';
import { MailPoet } from '../../mailpoet';

type Segment = {
  name: string;
  id?: string;
};

type SubscriberTag = {
  id: string;
  name: string;
  subscriber_id: string;
  tag_id: string;
};

type Props = {
  children?: ReactNode;
  dimension?: 'large';
  segments?: Segment[];
  subscriberTags?: SubscriberTag[];
  strings?: string[];
  variant?: TagVariant;
  isInverted?: boolean;
};

function Tags({
  children,
  dimension,
  segments,
  subscriberTags,
  strings,
  variant,
  isInverted,
}: Props) {
  return (
    <div className="mailpoet-tags">
      {children}
      {segments &&
        segments.map((segment) => {
          const tag = (
            <Tag key={segment.name} dimension={dimension} variant="list">
              {segment.name}
            </Tag>
          );
          if (!segment.id) {
            return tag;
          }
          const randomId = Math.random().toString(36).substring(2, 15);
          const tooltipId = `segment-tooltip-${randomId}`;

          return (
            <div key={randomId}>
              <Tooltip id={tooltipId} place="top">
                {MailPoet.I18n.t('viewFilteredSubscribersMessage')}
              </Tooltip>
              <a
                data-tip=""
                data-for={tooltipId}
                href={`admin.php?page=mailpoet-subscribers#/filter[segment=${segment.id}]`}
              >
                {tag}
              </a>
            </div>
          );
        })}
      {subscriberTags &&
        subscriberTags.map((subscriberTag) => {
          const randomId = Math.random().toString(36).substring(2, 15);
          const tooltipId = `tag-tooltip-${randomId}`;
          const tag = (
            <Tag
              key={subscriberTag.name}
              dimension={dimension}
              variant={variant || 'list'}
              isInverted={isInverted}
            >
              {subscriberTag.name}
            </Tag>
          );

          return (
            <div key={randomId}>
              <Tooltip id={tooltipId} place="top">
                {MailPoet.I18n.t('viewFilteredSubscribersMessage')}
              </Tooltip>
              <a
                data-tip=""
                data-for={tooltipId}
                href={`admin.php?page=mailpoet-subscribers#/filter[tag=${subscriberTag.tag_id}]`}
              >
                {tag}
              </a>
            </div>
          );
        })}
      {strings &&
        strings.map((string) => (
          <Tag
            key={string}
            dimension={dimension}
            variant={variant || 'list'} // due to backward compatibility we use `list` as the default value
            isInverted={isInverted}
          >
            {string}
          </Tag>
        ))}
    </div>
  );
}

export { Tags };
