<?php

namespace Prooph\ServiceBus\Plugin\Auditing;

use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\ActionEventListenerAggregate;
use Prooph\Common\Event\DetachAggregateHandlers;
use Prooph\ServiceBus\MessageBus;
use Psr\Log\LoggerInterface;

final class CommandAuditor implements ActionEventListenerAggregate
{
    use DetachAggregateHandlers;

    /** @var LoggerInterface */
    private $logger;

    /** @var MessageSerializer */
    private $messageSerializer;

    /**
     * @param LoggerInterface $logger
     * @param MessageSerializer $messageSerializer
     */
    public function __construct(LoggerInterface $logger, MessageSerializer $messageSerializer)
    {
        $this->logger = $logger;
        $this->messageSerializer = $messageSerializer;
    }

    /**
     * @param ActionEventEmitter $dispatcher
     */
    public function attach(ActionEventEmitter $dispatcher)
    {
        $onFinalizeCommandHandler = $dispatcher->attachListener(MessageBus::EVENT_FINALIZE, [$this, 'onFinalizeCommand']);
        $onErrorCommandHandler = $dispatcher->attachListener(MessageBus::EVENT_HANDLE_ERROR, [$this, 'onErrorCommand']);

        $this->trackHandler($onFinalizeCommandHandler);
        $this->trackHandler($onErrorCommandHandler);
    }

    /**
     * @param ActionEvent $event
     */
    public function onFinalizeCommand(ActionEvent $event)
    {
        if ($event->getParam(MessageBus::EVENT_PARAM_EXCEPTION)) {
            return;
        }

        $command = $event->getParam(MessageBus::EVENT_PARAM_MESSAGE);

        $data = $this->messageSerializer->serializeCommand($command);
        $data['success'] = true;
        $this->logger->info(json_encode($data));
    }

    /**
     * @param ActionEvent $event
     */
    public function onErrorCommand(ActionEvent $event)
    {
        $exception = $event->getParam(MessageBus::EVENT_PARAM_EXCEPTION);

        $command = $event->getParam(MessageBus::EVENT_PARAM_MESSAGE);
        $data = $this->messageSerializer->serializeCommand($command);
        $data['success'] = false;

        if ($exception instanceof \Exception) {
            $data['exception'] = $this->messageSerializer->serializeException($exception);
        }

        $this->logger->error(json_encode($data));
    }
}
