<?php
namespace MailPoet\Settings;

class Hosts {
  private static $_smtp = [
    'AmazonSES' => [
      'name' => 'Amazon SES',
      'emails' => 100,
      'interval' => 5,
      'fields' => [
        'region',
        'access_key',
        'secret_key',
      ],
      'regions' => [
        'US East (N. Virginia)' => 'us-east-1',
        'US West (Oregon)' => 'us-west-2',
        'EU (Ireland)' => 'eu-west-1',
      ],
    ],
    'SendGrid' => [
      'name' => 'SendGrid',
      'emails' => 100,
      'interval' => 5,
      'fields' => [
        'api_key',
      ],
    ],
  ];

  private static $_web = [
    '1and1' => [
        'name' => '1and1',
        'emails' => 30,
        'interval' => 5,
    ],
    'bluehost' => [
        'name' => 'BlueHost',
        'emails' => 70,
        'interval' => 30,
    ],
    'df' => [
        'name' => 'Df.eu',
        'emails' => 115,
        'interval' => 15,
    ],
    'dreamhost' => [
        'name' => 'DreamHost',
        'emails' => 25,
        'interval' => 15,
    ],
    'free' => [
        'name' => 'Free.fr',
        'emails' => 18,
        'interval' => 15,
    ],
    'froghost' => [
        'name' => 'FrogHost.com',
        'emails' => 490,
        'interval' => 30,
    ],
    'godaddy' => [
        'name' => 'GoDaddy',
        'emails' => 5,
        'interval' => 30,
    ],
    'goneo' => [
        'name' => 'Goneo',
        'emails' => 60,
        'interval' => 15,
    ],
    'googleapps' => [
        'name' => 'Google Apps',
        'emails' => 20,
        'interval' => 60,
    ],
    'greengeeks' => [
        'name' => 'GreenGeeks',
        'emails' => 45,
        'interval' => 30,
    ],
    'hawkhost' => [
        'name' => 'Hawkhost.com',
        'emails' => 500,
        'interval' => 15,
    ],
    'hivetec' => [
        'name' => 'Hivetec',
        'emails' => 20,
        'interval' => 15,
    ],
    'hostgator' => [
        'name' => 'Host Gator',
        'emails' => 115,
        'interval' => 15,
    ],
    'hosting2go' => [
        'name' => 'Hosting 2GO',
        'emails' => 45,
        'interval' => 15,
    ],
    'hostmonster' => [
        'name' => 'Host Monster',
        'emails' => 115,
        'interval' => 15,
    ],
    'infomaniak' => [
        'name' => 'Infomaniak',
        'emails' => 20,
        'interval' => 15,
    ],
    'justhost' => [
        'name' => 'JustHost',
        'emails' => 70,
        'interval' => 30,
    ],
    'laughingsquid' => [
        'name' => 'Laughing Squid',
        'emails' => 20,
        'interval' => 15,
    ],
    'lunarpages' => [
        'name' => 'Lunarpages',
        'emails' => 19,
        'interval' => 15,
    ],
    'mediatemple' => [
        'name' => 'Media Temple',
        'emails' => 115,
        'interval' => 15,
    ],
    'netfirms' => [
        'name' => 'Netfirms',
        'emails' => 200,
        'interval' => 60,
    ],
    'netissime' => [
        'name' => 'Netissime',
        'emails' => 100,
        'interval' => 15,
    ],
    'one' => [
        'name' => 'One.com',
        'emails' => 100,
        'interval' => 15,
    ],
    'ovh' => [
        'name' => 'OVH',
        'emails' => 50,
        'interval' => 15,
    ],
    'phpnet' => [
        'name' => 'PHPNet',
        'emails' => 15,
        'interval' => 15,
    ],
    'planethoster' => [
        'name' => 'PlanetHoster',
        'emails' => 90,
        'interval' => 30,
    ],
    'rochen' => [
        'name' => 'Rochen',
        'emails' => 40,
        'interval' => 15,
    ],
    'site5' => [
        'name' => 'Site5',
        'emails' => 40,
        'interval' => 15,
    ],
    'siteground' => [
        'name' => 'Siteground',
        'emails' => 95,
        'interval' => 15,
    ],
    'synthesis' => [
        'name' => 'Synthesis',
        'emails' => 250,
        'interval' => 15,
    ],
    'techark' => [
        'name' => 'Techark',
        'emails' => 60,
        'interval' => 15,
    ],
    'vexxhost' => [
        'name' => 'Vexxhost',
        'emails' => 60,
        'interval' => 15,
    ],
    'vps' => [
        'name' => 'VPS.net',
        'emails' => 90,
        'interval' => 30,
    ],
    'webcity' => [
        'name' => 'Webcity',
        'emails' => 19,
        'interval' => 15,
    ],
    'westhost' => [
        'name' => 'Westhost',
        'emails' => 225,
        'interval' => 15,
    ],
    'wpwebhost' => [
        'name' => 'Wpwebhost.com',
        'emails' => 95,
        'interval' => 30,
    ],
  ];

  static function getWebHosts() {
    return static::$_web;
  }

  static function getSMTPHosts() {
    return static::$_smtp;
  }
}