<?php
/*
 * This file is part of the legalweb/psb-command-auditing package.
 * (c) 2015-2016, Legalwebb UK LTD
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\ServiceBus\Plugin\Auditing;

use Prooph\Common\Messaging\DomainMessage;
use Prooph\Common\Messaging\MessageConverter;

class RawMessageSerializer implements MessageSerializer
{
    /** @var MessageConverter */
    private $messageConverter;

    /** @var array */
    private $meta;

    /**
     * @param MessageConverter $messageConverter
     * @param array $meta May contain user id, or even ip address.
     */
    public function __construct(MessageConverter $messageConverter, array $meta)
    {
        $this->messageConverter = $messageConverter;
        $this->meta = $meta;
    }

    /**
     * @param \Exception $exception
     * @return array
     */
    public function serializeException(\Exception $exception)
    {
        return [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];
    }

    /**
     * @param mixed $command
     * @return array
     */
    public function serializeCommand($command)
    {
        $data = [];

        if ($command instanceof DomainMessage) {
            $data = $this->messageConverter->convertToArray($command);
        }

        if (is_object($command)) {
            $command = get_class($command);
        }

        return [
            'command' => $command,
            'data' => $data,
            'meta' => $this->meta,
        ];
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setMeta($key, $value)
    {
        $this->meta[$key] = $value;
    }

    /**
     * @return array
     */
    public function meta()
    {
        return $this->meta;
    }
}
