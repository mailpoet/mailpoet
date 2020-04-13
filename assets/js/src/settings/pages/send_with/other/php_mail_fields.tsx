import React from 'react';
import { Label, Inputs } from 'settings/components';
import { t, onChange } from 'common/functions';
import { useSetting } from 'settings/store/hooks';
import SendingFrequency from './sending_frequency';

export default function PHPMailFields() {
  const [hostName, setHostName] = useSetting('web_host');
  const host = hosts[hostName];
  return (
    <>
      <Label title={t('yourHost')} htmlFor="mailpoet_web_host" />
      <Inputs>
        <select id="mailpoet_web_host" value={hostName} onChange={onChange(setHostName)}>
          {Object.values(hosts).map((h) => <option key={h.name} value={h.name}>{h.label}</option>)}
        </select>
      </Inputs>
      <SendingFrequency recommendedEmails={host.emails} recommendedInterval={host.interval} />
    </>
  );
}

const hosts = {
  manual: {
    name: 'manual', label: t('notListed'), emails: '25', interval: '5',
  },
  '1and1': {
    name: '1and1', label: '1and1', emails: '30', interval: '5',
  },
  bluehost: {
    name: 'bluehost', label: 'BlueHost', emails: '70', interval: '30',
  },
  df: {
    name: 'df', label: 'Df.eu', emails: '115', interval: '15',
  },
  dreamhost: {
    name: 'dreamhost', label: 'DreamHost', emails: '25', interval: '15',
  },
  free: {
    name: 'free', label: 'Free.fr', emails: '18', interval: '15',
  },
  froghost: {
    name: 'froghost', label: 'FrogHost.com', emails: '490', interval: '30',
  },
  godaddy: {
    name: 'godaddy', label: 'GoDaddy', emails: '5', interval: '30',
  },
  goneo: {
    name: 'goneo', label: 'Goneo', emails: '60', interval: '15',
  },
  googleapps: {
    name: 'googleapps', label: 'Google Apps', emails: '20', interval: '60',
  },
  greengeeks: {
    name: 'greengeeks', label: 'GreenGeeks', emails: '45', interval: '30',
  },
  hawkhost: {
    name: 'hawkhost', label: 'Hawkhost.com', emails: '500', interval: '15',
  },
  hivetec: {
    name: 'hivetec', label: 'Hivetec', emails: '20', interval: '15',
  },
  hostgator: {
    name: 'hostgator', label: 'Host Gator', emails: '115', interval: '15',
  },
  hosting2go: {
    name: 'hosting2go', label: 'Hosting 2GO', emails: '45', interval: '15',
  },
  hostmonster: {
    name: 'hostmonster', label: 'Host Monster', emails: '115', interval: '15',
  },
  infomaniak: {
    name: 'infomaniak', label: 'Infomaniak', emails: '20', interval: '15',
  },
  justhost: {
    name: 'justhost', label: 'JustHost', emails: '70', interval: '30',
  },
  laughingsquid: {
    name: 'laughingsquid', label: 'Laughing Squid', emails: '20', interval: '15',
  },
  lunarpages: {
    name: 'lunarpages', label: 'Lunarpages', emails: '19', interval: '15',
  },
  mediatemple: {
    name: 'mediatemple', label: 'Media Temple', emails: '115', interval: '15',
  },
  netfirms: {
    name: 'netfirms', label: 'Netfirms', emails: '200', interval: '60',
  },
  netissime: {
    name: 'netissime', label: 'Netissime', emails: '100', interval: '15',
  },
  one: {
    name: 'one', label: 'One.com', emails: '100', interval: '15',
  },
  ovh: {
    name: 'ovh', label: 'OVH', emails: '50', interval: '15',
  },
  phpnet: {
    name: 'phpnet', label: 'PHPNet', emails: '15', interval: '15',
  },
  planethoster: {
    name: 'planethoster', label: 'PlanetHoster', emails: '90', interval: '30',
  },
  rochen: {
    name: 'rochen', label: 'Rochen', emails: '40', interval: '15',
  },
  site5: {
    name: 'site5', label: 'Site5', emails: '40', interval: '15',
  },
  siteground: {
    name: 'siteground', label: 'Siteground', emails: '95', interval: '15',
  },
  synthesis: {
    name: 'synthesis', label: 'Synthesis', emails: '250', interval: '15',
  },
  techark: {
    name: 'techark', label: 'Techark', emails: '60', interval: '15',
  },
  vexxhost: {
    name: 'vexxhost', label: 'Vexxhost', emails: '60', interval: '15',
  },
  vps: {
    name: 'vps', label: 'VPS.net', emails: '90', interval: '30',
  },
  webcity: {
    name: 'webcity', label: 'Webcity', emails: '19', interval: '15',
  },
  westhost: {
    name: 'westhost', label: 'Westhost', emails: '225', interval: '15',
  },
  wpwebhost: {
    name: 'wpwebhost', label: 'Wpwebhost.com', emails: '95', interval: '30',
  },
};
