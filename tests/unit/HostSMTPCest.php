<?php
use \UnitTester;

class HostSMTPCest {
    public function _before() {
        $this->hosts = \MailPoet\Host\SMTP::getList();
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
                && array_key_exists('api', $host_info)
                && is_bool($host_info['api'])
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
}