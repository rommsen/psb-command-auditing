<?php
/*
 * This file is part of the legalweb/psb-command-auditing package.
 * (c) 2015-2016, Legalwebb UK LTD
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
