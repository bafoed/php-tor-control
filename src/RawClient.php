<?php
/**
 * File: RawClient.php
 * Copyright 2016 Anton Lempinen <bafoed@bafoed.ru>
 * This file is part of TorControl project.
 */

/**
 * Raw part of Tor control.
 * Works with the socket connection.
 */
class RawClient
{
    /**
     * Print debug messages
     * @var bool
     */
    protected $debug = false;

    /**
     * Handler for socket connection.
     * @var resource
     */
    protected $_socketHandler;

    /**
     * Tor control socket response code.
     * @var int
     */
    protected $_resultCode;

    /**
     * Tor control socket response message.
     * @var string
     */
    protected $_resultMessage;

    /**
     * Tor control socket response data.
     * May be empty if called method returned no data.
     * @var array
     */
    protected $_resultData;

    /**
     * RawClient constructor.
     * @param string $socketHost Address of the socket to connect to.
     * @param int $socketPort Port of the socket to connect to.
     * @throws RawClientException
     */
    public function __construct($socketHost =  'localhost', $socketPort = 9051)
    {
        $this->_debugLog('Creating socket', '***');
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$socket) {
            throw new RawClientException('Could not create socket: ' . socket_strerror(socket_last_error()));
        }

        $this->_debugLog(sprintf('Connecting to %s:%s', $socketHost, $socketPort), '***');

        if (!socket_connect($socket, $socketHost, $socketPort)) {
            throw new RawClientException('Could not to connect tot socket: ' . socket_strerror(socket_last_error()));
        }

        $this->_debugLog('Connected');

        $this->_socketHandler = $socket;
    }

    /**
     * Destroys socket connection.
     */
    public function __destruct()
    {
        socket_close($this->_socketHandler);
    }

    /**
     * Executes command on Tor control socket.
     * Line-breaks will be escaped as Tor do not support them.
     * @param string $command Command to execute. Command parts can be passed as arguments
     * @throws RawClientException
     * @return bool
     */
    public function exec($command)
    {
        $command = implode(' ', func_get_args());
        $this->_debugLog(trim($command), '>>>');

        socket_write($this->_socketHandler, str_replace("\n", '\n', $command) . PHP_EOL);

        $buffer = '';
        socket_recv($this->_socketHandler, $buffer, 2048, 0);
        $buffer = $this->sanitize($buffer);
        $this->_debugLog(PHP_EOL . trim($buffer), '<<<');

        $resultData = explode("\n", trim($buffer));
        $lastLine = array_pop($resultData);

        list($code, $message) = explode(' ', $lastLine, 2);
        if (!$code || !$message) {
            throw new RawClientException('Error while parsing answer: ' . $lastLine);
        }

        $this->_resultCode = intval($code);
        $this->_resultData = $resultData;
        $this->_resultMessage = $message;

        $this->_debugLog(sprintf('Code    - (%s)', trim($this->_resultCode)), '***');
        $this->_debugLog(sprintf('Message - (%s)', trim($this->_resultMessage)), '***');
        $this->_debugLog(PHP_EOL);

        return true;
    }

    /**
     * Returns result message from Tor control.
     * @return string
     */
    public function getResultMessage()
    {
        return $this->_resultMessage;
    }

    /**
     * Returns result code from Tor control.
     * @return int
     */
    public function getResultCode()
    {
        return $this->_resultCode;
    }

    /**
     * Returns result data from Tor control.
     * @return array
     */
    public function getResultData()
    {
        return $this->_resultData;
    }

    /**
     * Print debug info if debug mode enabled
     * @param string $message Message to be printed
     * @param string $prepend
     */
    private function _debugLog($message, $prepend = '***')
    {
        if ($this->debug) {
            $dateTime = date('H:i:s');
            echo sprintf('[%s] [%s] %s', $dateTime, $prepend, $message) . PHP_EOL;
        }
    }

    /**
     * Remove special chars from string
     * @param string $string
     * @return string mixed
     */
    public function sanitize($string) {
        return preg_replace('/[^\x0A\x20-\x7E]/', '', $string);
    }
}