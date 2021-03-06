<?php
/*
 * This file is part of the legalweb/psb-command-auditing package.
 * (c) 2015-2016, Legalwebb UK LTD
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\ServiceBus\Plugin\Auditing;

class SecretMessageSerializer implements MessageSerializer
{
    /** @var MessageSerializer */
    private $messageSerializer;

    /** @var array */
    private $secretFields;

    /** @var string */
    private $placeHolder;

    /**
     * @param MessageSerializer $messageSerializer
     * @param array $secretFields
     * @param string $placeHolder
     */
    public function __construct(MessageSerializer $messageSerializer, array $secretFields, $placeHolder = '[SECRET]')
    {
        $this->messageSerializer = $messageSerializer;
        $this->secretFields = $secretFields;
        $this->placeHolder = $placeHolder;
    }

    /**
     * @param \Exception $exception
     * @return array
     */
    public function serializeException(\Exception $exception)
    {
        return $this->messageSerializer->serializeException($exception);
    }

    /**
     * @param mixed $command
     * @return array
     */
    public function serializeCommand($command)
    {
        $serializedCommand = $this->messageSerializer->serializeCommand($command);

        return $this->obscureArray($serializedCommand);
    }

    /**
     * @param array $data
     * @return array
     */
    private function obscureArray(array $data)
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->secretFields, true)) {
                $data[$key] = $this->placeHolder;
            }

            if (is_array($value)) {
                $data[$key] = $this->obscureArray($value);
            }
        }

        return $data;
    }
}
