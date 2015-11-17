<?php

namespace Prooph\ServiceBus\Plugin\Auditing;

interface MessageSerializer
{
    /**
     * @param \Exception $exception
     * @return array
     */
    public function serializeException(\Exception $exception);

    /**
     * @param mixed $command
     * @return array
     */
    public function serializeCommand($command);
}
