<?php

namespace Smt\Pmpd\Response\Impl;

use Smt\Pmpd\Response\Response;

/**
 * Represents socket response
 * @package Smt\Pmpd\Response\Impl
 * @author Kirill Saksin <kirillsaksin@yandex.ru>
 */
class SocketResponse implements Response
{
    protected $responseData = [];
    protected $context;
    private $empty = false;

    const ACK_PATTERN = '/^ACK\ \[([0-9]+)\@([0-9]+)\]\ \{(\w*)\}\ (.*)$/';

    /**
     * Constructor.
     * @param array $raw
     * @param string $context
     */
    private function __construct(array $raw, $context = '')
    {
        if (count($raw) == 0) {
            $this->empty = true;
        }

        $this->setContext($context);

        $this->parseData($raw);
    }

    /**
     * Create response from raw one
     * @param array $raw Raw data (lines)
     * @return FailSocketResponse|SocketResponse
     */
    public static function fromRaw(array $raw)
    {
        $last = $raw[count($raw) - 2];
        if (strncmp($last, 'ACK', 3) === 0) {
            return self::produceFailResponse($last);
        }
        array_pop($raw);
        array_pop($raw);
        return new self($raw);
    }

    /** {@inheritdoc} */
    public function isEmpty()
    {
        return $this->empty;
    }

    /** {@inheritdoc} */
    public function get($key, $default = null)
    {
        if (isset($this->responseData[$key])) {
            return $this->responseData[$key];
        }
        return $default;
    }

    /**
     * @param string $error Error string
     * @return FailSocketResponse
     */
    private static function produceFailResponse($error)
    {
        preg_match(self::ACK_PATTERN, $error, $matches);
        return FailSocketResponse::create()
            ->setErrorCode(isset($matches[1]) ? $matches[1] : 0)
            ->setLineNumber(isset($matches[2]) ? $matches[2] : 0)
            ->setCommand(isset($matches[3]) ? $matches[3] : '')
            ->setMessage(isset($matches[4]) ? $matches[4] : '')
        ;
    }

    /**
     * @param string $context 
     * @return SocketResponse
     */
    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @param $value
     * @return bool
     */
    public function contextIs($value)
    {
        return strpos($this->context, $value) === 0;
    }

    protected function parseData($raw)
    {
        switch(true) {
            case $this->contextIs('listplaylistinfo') :
                return $this->parseAsCollectionOf('file', $raw);
            default :
                return $this->parseDefault($raw);
        }
    }

    protected function parseDefault($raw)
    {
        foreach ($raw as $line) {
            list($key, $value) = $this->parseLine($line);

            // Convert repeated kes to data array
            if (array_key_exists($key, $this->responseData)) {

                if (!is_array($this->responseData[$key])) {
                    $this->responseData[$key] = [$this->responseData[$key]];
                }

                $this->responseData[$key][] = $value;

            } else {
                // Default behavior
                $this->responseData[$key] = $value;
            }
        }
        return $this;
    }

    protected function parseAsCollectionOf($splitKey, $raw)
    {
        $data = [];
        foreach ($raw AS $line) {
            list($key, $value) = $this->parseLine($line);

            if (!$lineData) {
                continue;
            }

            if ($key === $splitKey) {
                // Add new element
                $data[] = [];
            }
            $pos = count($data) - 1;
            if ($pos >= 0) {
                $data[$pos][$key] = $value;
            }
        }

        $this->responseData = $data;
        return $this;
    }

    protected function parseLine($line)
    {
        return preg_match('/(\w+)\:\ (.*)$/', $line, $matches)
            ? (array_shift($matches) ? $matches : null)
            : null;
    }
}
