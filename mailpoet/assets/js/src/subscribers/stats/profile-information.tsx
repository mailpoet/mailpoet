import {
  Button,
  Card,
  CardBody,
  CardHeader,
  Flex,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useLocation, useNavigate } from 'react-router-dom';
import { SegmentTags, SubscriberTags } from 'common';
import { SubscriberProfile } from '../types';

type Props = {
  profile: SubscriberProfile;
  subscriberId: number;
};

function EmptyValue(): JSX.Element {
  return <span className="mailpoet-subscriber-stats-empty-value">-</span>;
}

function TextValue({ value }: { value: string | null | undefined }) {
  if (!value) {
    return <EmptyValue />;
  }
  return <>{value}</>;
}

function ProfileRow({
  label,
  children,
}: {
  label: string;
  children: JSX.Element | string;
}): JSX.Element {
  return (
    <div className="mailpoet-subscriber-stats-profile-row">
      <dt>{label}</dt>
      <dd>{children}</dd>
    </div>
  );
}

export function ProfileInformation({
  profile,
  subscriberId,
}: Props): JSX.Element {
  const location = useLocation();
  const navigate = useNavigate();

  return (
    <Card className="mailpoet-subscriber-stats-card" size="medium">
      <CardHeader className="mailpoet-subscriber-stats-card-header">
        <Flex align="center" justify="space-between">
          <h2 className="mailpoet-subscriber-stats-card-title">
            {__('Profile information', 'mailpoet')}
          </h2>
          <Button
            variant="secondary"
            onClick={() =>
              navigate(`/edit/${subscriberId}`, {
                state: {
                  backUrl: location.pathname,
                },
              })
            }
          >
            {__('Edit', 'mailpoet')}
          </Button>
        </Flex>
      </CardHeader>
      <CardBody>
        <dl className="mailpoet-subscriber-stats-profile-list">
          <ProfileRow label={__('First name', 'mailpoet')}>
            <TextValue value={profile.first_name} />
          </ProfileRow>
          <ProfileRow label={__('Last name', 'mailpoet')}>
            <TextValue value={profile.last_name} />
          </ProfileRow>
          <ProfileRow label={__('Email address', 'mailpoet')}>
            <TextValue value={profile.email} />
          </ProfileRow>
          {profile.shipping_address.length > 0 && (
            <ProfileRow label={__('Shipping', 'mailpoet')}>
              <>
                {profile.shipping_address.map((line) => (
                  <div key={line}>{line}</div>
                ))}
              </>
            </ProfileRow>
          )}
          {profile.custom_fields.map((field) => (
            <ProfileRow key={field.id} label={field.name}>
              <TextValue value={field.value} />
            </ProfileRow>
          ))}
          <ProfileRow label={__('Subscriber tags', 'mailpoet')}>
            {profile.tags.length > 0 ? (
              <SubscriberTags
                subscribers={profile.tags}
                variant="wordpress"
                isInverted
              />
            ) : (
              <EmptyValue />
            )}
          </ProfileRow>
          <ProfileRow label={__('Lists', 'mailpoet')}>
            {profile.segments.length > 0 ? (
              <SegmentTags segments={profile.segments} />
            ) : (
              <EmptyValue />
            )}
          </ProfileRow>
        </dl>
      </CardBody>
    </Card>
  );
}

ProfileInformation.displayName = 'ProfileInformation';
