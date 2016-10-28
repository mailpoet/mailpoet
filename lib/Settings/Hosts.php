<?php
namespace MailPoet\Settings;

class Hosts {
  private static $_smtp = array(
    'AmazonSES' => array(
      'name' => 'Amazon SES',
      'emails' => 100,
      'interval' => 5,
      'fields' => array(
        'region',
        'access_key',
        'secret_key'
      ),
      'regions' => array(
        'US East (N. Virginia)' => 'us-east-1',
        'US West (Oregon)' => 'us-west-2',
        'EU (Ireland)' => 'eu-west-1'
      )
    ),
    'SendGrid' => array(
      'name' => 'SendGrid',
      'emails' => 100,
      'interval' => 5,
      'fields' => array(
        'api_key'
      )
    )
  );

  private static $_web = array(
    '1and1' => array(
        'name' => '1and1',
        'emails' => 30,
        'interval' => 5,
    ),
    'bluehost' => array(
        'name' => 'BlueHost',
        'emails' => 70,
        'interval' => 30,
    ),
    'df' => array(
        'name' => 'Df.eu',
        'emails' => 115,
        'interval' => 15,
    ),
    'dreamhost' => array(
        'name' => 'DreamHost',
        'emails' => 25,
        'interval' => 15,
    ),
    'free' => array(
        'name' => 'Free.fr',
        'emails' => 18,
        'interval' => 15,
    ),
    'froghost' => array(
        'name' => 'FrogHost.com',
        'emails' => 490,
        'interval' => 30,
    ),
    'godaddy' => array(
        'name' => 'GoDaddy',
        'emails' => 5,
        'interval' => 30,
    ),
    'goneo' => array(
        'name' => 'Goneo',
        'emails' => 60,
        'interval' => 15,
    ),
    'googleapps' => array(
        'name' => 'Google Apps',
        'emails' => 20,
        'interval' => 60,
    ),
    'greengeeks' => array(
        'name' => 'GreenGeeks',
        'emails' => 45,
        'interval' => 30,
    ),
    'hawkhost' => array(
        'name' => 'Hawkhost.com',
        'emails' => 500,
        'interval' => 15,
    ),
    'hivetec' => array(
        'name' => 'Hivetec',
        'emails' => 20,
        'interval' => 15,
    ),
    'hostgator' => array(
        'name' => 'Host Gator',
        'emails' => 115,
        'interval' => 15,
    ),
    'hosting2go' => array(
        'name' => 'Hosting 2GO',
        'emails' => 45,
        'interval' => 15,
    ),
    'hostmonster' => array(
        'name' => 'Host Monster',
        'emails' => 115,
        'interval' => 15,
    ),
    'infomaniak' => array(
        'name' => 'Infomaniak',
        'emails' => 20,
        'interval' => 15,
    ),
    'justhost' => array(
        'name' => 'JustHost',
        'emails' => 70,
        'interval' => 30,
    ),
    'laughingsquid' => array(
        'name' => 'Laughing Squid',
        'emails' => 20,
        'interval' => 15,
    ),
    'lunarpages' => array(
        'name' => 'Lunarpages',
        'emails' => 19,
        'interval' => 15,
    ),
    'mediatemple' => array(
        'name' => 'Media Temple',
        'emails' => 115,
        'interval' => 15,
    ),
    'netfirms' => array(
        'name' => 'Netfirms',
        'emails' => 200,
        'interval' => 60,
    ),
    'netissime' => array(
        'name' => 'Netissime',
        'emails' => 100,
        'interval' => 15,
    ),
    'one' => array(
        'name' => 'One.com',
        'emails' => 100,
        'interval' => 15,
    ),
    'ovh' => array(
        'name' => 'OVH',
        'emails' => 50,
        'interval' => 15,
    ),
    'phpnet' => array(
        'name' => 'PHPNet',
        'emails' => 15,
        'interval' => 15,
    ),
    'planethoster' => array(
        'name' => 'PlanetHoster',
        'emails' => 90,
        'interval' => 30,
    ),
    'rochen' => array(
        'name' => 'Rochen',
        'emails' => 40,
        'interval' => 15,
    ),
    'site5' => array(
        'name' => 'Site5',
        'emails' => 40,
        'interval' => 15,
    ),
    'siteground' => array(
        'name' => 'Siteground',
        'emails' => 95,
        'interval' => 15,
    ),
    'synthesis' => array(
        'name' => 'Synthesis',
        'emails' => 250,
        'interval' => 15,
    ),
    'techark' => array(
        'name' => 'Techark',
        'emails' => 60,
        'interval' => 15,
    ),
    'vexxhost' => array(
        'name' => 'Vexxhost',
        'emails' => 60,
        'interval' => 15,
    ),
    'vps' => array(
        'name' => 'VPS.net',
        'emails' => 90,
        'interval' => 30,
    ),
    'webcity' => array(
        'name' => 'Webcity',
        'emails' => 19,
        'interval' => 15,
    ),
    'westhost' => array(
        'name' => 'Westhost',
        'emails' => 225,
        'interval' => 15,
    ),
    'wpwebhost' => array(
        'name' => 'Wpwebhost.com',
        'emails' => 95,
        'interval' => 30,
    )
  );

  static function getWebHosts() {
    return static::$_web;
  }

  static function getSMTPHosts() {
    return static::$_smtp;
  }
}