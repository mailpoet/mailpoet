import { ReactNode } from 'react';
import { Tag, TagVariant } from './tag';
import { Tooltip } from '../tooltip/tooltip';
import { MailPoet } from '../../mailpoet';

type SharedTagProps = {
  children?: ReactNode;
  dimension?: 'large';
  variant?: TagVariant;
  isInverted?: boolean;
};

type TagData = {
  name: string;
  target?: string;
  tooltip?: string;
};

type TagProps = SharedTagProps & {
  tags: TagData[];
};

type StringTagsProps = SharedTagProps & {
  strings: string[];
};

type Segment = {
  name: string;
  id?: string;
};

type SegmentTagsProps = SharedTagProps & {
  segments: Segment[];
};

type SubscriberTag = {
  id: string;
  name: string;
  subscriber_id: string;
  tag_id: string;
};

type SubscriberTagsProps = SharedTagProps & {
  subscribers: SubscriberTag[];
};

function Tags({ children, tags, dimension, variant, isInverted }: TagProps) {
  return (
    <div className="mailpoet-tags">
      {children}
      {tags.map((item) => {
        const tag = (
          <Tag
            key={item.name}
            dimension={dimension}
            variant={variant || 'list'}
            isInverted={isInverted}
          >
            {item.name}
          </Tag>
        );
        if (!item.target) {
          return tag;
        }

        const randomId = Math.random().toString(36).substring(2, 15);
        const tooltipId = `segment-tooltip-${randomId}`;
        return (
          <div key={randomId}>
            {item.tooltip && (
              <Tooltip id={tooltipId} place="top">
                {MailPoet.I18n.t('viewFilteredSubscribersMessage')}
              </Tooltip>
            )}
            <a data-tip="" data-for={tooltipId} href={item.target}>
              {tag}
            </a>
          </div>
        );
      })}
    </div>
  );
}

function StringTags({ children, strings, ...props }: StringTagsProps) {
  const tags: TagData[] = strings.map((item) => ({
    name: item,
  }));
  return (
    <Tags tags={tags} {...props}>
      {children}
    </Tags>
  );
}

function SegmentTags({ children, segments, ...props }: SegmentTagsProps) {
  const tags: TagData[] = segments.map((segment) => ({
    name: segment.name,
    target: segment.id
      ? `admin.php?page=mailpoet-subscribers#/filter[segment=${segment.id}]`
      : undefined,
    tooltip: MailPoet.I18n.t('viewFilteredSubscribersMessage'),
  }));
  return (
    <Tags tags={tags} {...props}>
      {children}
    </Tags>
  );
}

function SubscriberTags({
  children,
  subscribers,
  ...props
}: SubscriberTagsProps) {
  const tags: TagData[] = subscribers.map((item) => ({
    name: item.name,
    target: `admin.php?page=mailpoet-subscribers#/filter[tag=${item.tag_id}]`,
    tooltip: MailPoet.I18n.t('viewFilteredSubscribersMessage'),
  }));
  return (
    <Tags tags={tags} {...props}>
      {children}
    </Tags>
  );
}

export { SegmentTags, StringTags, SubscriberTags };
