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

    const ACK_PATTERN = '/^ACK\ \[([0-9]+)\@([0-9]+)\]\ \{(\w+)\}\ (.*)$/';

    /**
     * Constructor.
     * @param array $raw
     */
    private function __construct(array $raw)
    {
        if (count($raw) == 0) {
            $this->empty = true;
        }
        foreach ($raw as $responseLine) {
            preg_match('/(\w+)\:\ (.*)$/', $responseLine, $matches);

            // Convert repeated kes to data array
            if (array_key_exists($matches[1], $this->responseData)) {

                if (!is_array($this->responseData[$matches[1]])) {
                    $this->responseData[$matches[1]] = [$this->responseData[$matches[1]]];
                }

                $this->responseData[$matches[1]][] = $matches[2];

            } else {
                // Default behavior
                $this->responseData[$matches[1]] = $matches[2];
            }
        }
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
            ->setLineNumber(isset($matches[1]) ? $matches[2] : 0)
            ->setCommand(isset($matches[1]) ? $matches[3] : 0)
            ->setMessage(isset($matches[1]) ? $matches[4] : 0)
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
}
