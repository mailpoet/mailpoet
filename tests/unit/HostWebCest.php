<?php

class HostWebCest {
    public function _before() {
        $this->hosts = \MailPoet\Host\Web::getList();
    }

    // tests
    public function it_has_a_list_of_hosts() {
        expect($this->hosts)->notEmpty();
    }

    public function it_has_proper_data_for_hosts() {
        $valid_host_count = 0;
        $host_count = count($this->hosts);

        foreach($this->hosts as $host_key => $host_info) {
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

    public function it_should_return_default_limitations() {
        $limitations = \MailPoet\Host\Web::getLimitations();

        expect($limitations['emails'])
          ->equals(\MailPoet\Host\Web::DEFAULT_FREQUENCY_EMAILS);

        expect($limitations['interval'])
          ->equals(\MailPoet\Host\Web::DEFAULT_FREQUENCY_INTERVAL);
    }

    public function it_should_return_host_limitations() {
        $host_key = array_shift(array_keys($this->hosts));
        $host = $this->hosts[$host_key];

        $host_limitations = \MailPoet\Host\Web::getLimitations($host_key);

        expect($host_limitations['emails'])
          ->equals($host['emails']);

        expect($host_limitations['interval'])
          ->equals($host['interval']);
    }
}
