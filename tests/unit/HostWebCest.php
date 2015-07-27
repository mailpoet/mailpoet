<?php

class HostWebCest {
    // tests
    public function itHasAListOfHosts() {
        expect(\MailPoet\Host\Web::getList())->notEmpty();
    }

    public function itHasValidDataForHosts() {
        $valid_host_count = 0;
        $hosts = \MailPoet\Host\Web::getList();
        $host_count = count($hosts);

        foreach($hosts as $host_key => $host_info) {
            if(array_key_exists('name', $host_info)
                && is_string($host_info['name'])
                && array_key_exists('emails', $host_info)
                && is_int($host_info['emails'])
                && array_key_exists('interval', $host_info)
                && is_int($host_info['interval'])
            ) {
                $valid_host_count++;
            }
        }

        expect($valid_host_count)->equals($host_count);
    }

    public function itHasDefaultSendingLimitations() {
        $limitations = \MailPoet\Host\Web::getLimitations();

        expect($limitations['emails'])
          ->equals(\MailPoet\Host\Web::DEFAULT_FREQUENCY_EMAILS);

        expect($limitations['interval'])
          ->equals(\MailPoet\Host\Web::DEFAULT_FREQUENCY_INTERVAL);
    }

    public function itShoudReturnHostLimitations() {
        $hosts = \MailPoet\Host\Web::getList();
        $host_keys = array_keys($hosts);
        $host_key = array_shift($host_keys);
        $host = $hosts[$host_key];

        $host_limitations = \MailPoet\Host\Web::getLimitations($host_key);

        expect($host_limitations['emails'])
          ->equals($host['emails']);

        expect($host_limitations['interval'])
          ->equals($host['interval']);
    }
}
