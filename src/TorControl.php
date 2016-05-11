<?php
/**
 * File: TorControl.php
 * Copyright 2016 Anton Lempinen <bafoed@bafoed.ru>
 * This file is part of TorControl project.
 */

/**
 * Main class of Tor control.
 * Send Tor-specified messages and handles responses.
 */
class TorControl
{
    /**
     * Raw client for messaging with tor
     * @var RawClient
     */
    protected $_rawClient;

    /**
     * TorControl constructor.
     * @param string $socketHost Address of the socket to connect to.
     * @param int $socketPort Port of the socket to connect to.
     * @param string $controlPassword
     * @throws RawClientException
     * @throws TorControlException
     */
    public function __construct($socketHost =  'localhost', $socketPort = 9051, $controlPassword = '')
    {
        $this->_rawClient = new RawClient($socketHost, $socketPort);
        $controlPassword = '"' . $controlPassword . '"'; // tor requires to enquote password
        if ($this->_rawClient->exec('AUTHENTICATE', $controlPassword)) {
            $resultCode = $this->_rawClient->getResultCode();
            if ($resultCode !== 250) {
                throw new \TorControlException('Failed to authenticate: ' . $this->_rawClient->getResultMessage());
            }
        }
    }

    /**
     * Closes connection to Tor control socket
     * @throws RawClientException
     */
    public function __destruct()
    {
        if ($this->_rawClient) {
            $this->_rawClient->exec('QUIT');
        }
    }

    /**
     * Calls to GETINFO function
     * @param mixed $params Params parts can be passed as arguments.
     * @return array|string
     * @throws RawClientException
     */
    public function _getInfo($params)
    {
        $params = implode(' ', func_get_args());
        $this->_rawClient->exec('GETINFO', $params);
        $resultData = $this->_rawClient->getResultData();
        foreach ($resultData as $key => &$value) {
            $value = str_replace(
                array(
                    sprintf('250-%s=', func_get_arg(0)),  // replacing strings like '250-version=(...)'
                    sprintf('250+%s=', func_get_arg(0))   // replacing strings like '250+circuit-status=(...)'
                ),
                '',
                $value
            );
        }

        return $resultData;
    }

    /**
     * Return tord version.
     * @return string
     */
    public function getVersion() {
        return reset($this->_getInfo('version'));
    }

    /**
     * Return servers IP address.
     * Can take about 4 seconds to fetch.
     * @return string
     */
    public function getAddress() {
        return reset($this->_getInfo('address'));
    }

    /**
     * Return accounting stats if enabled
     * @return array
     * @throws TorControlException
     */
    public function getAccountingStats() {
        $enabled = reset($this->_getInfo('accounting/enabled'));
        if ($enabled != '1') {
            throw new TorControlException('Accounting is not enabled.');
        }

        $status = reset($this->_getInfo('accounting/hibernating'));
        $intervalEnd = reset($this->_getInfo('accounting/interval-end'));
        $used = reset($this->_getInfo('accounting/bytes'));
        $left = reset($this->_getInfo('accounting/bytes-left'));
        list ($usedRead, $usedWritten) = explode(' ', $used);
        list ($leftRead, $leftWritten) = explode(' ', $left);

        return array(
            'status' => $status,
            'interval_end' => strtotime($intervalEnd),
            'read_bytes' => floatval($usedRead),
            'read_bytes_left' => floatval($leftRead),
            'read_limit' => floatval($usedRead) + floatval($leftRead),
            'written_bytes' => floatval($usedWritten),
            'write_bytes_left' => floatval($leftWritten),
            'write_limit' => floatval($usedWritten) + floatval($leftWritten)
        );
    }

    /**
     * Return username of tord user
     * @return mixed
     */
    public function getUser() {
        return reset($this->_getInfo('process/user'));
    }

    /**
     * Force tor to create new identity
     * @throws RawClientException
     */
    public function changeIP() {
        $this->_rawClient->exec('SIGNAL NEWNYM');
    }
}