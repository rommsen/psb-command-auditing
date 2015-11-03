<?php

namespace Prooph\ServiceBus\Plugin\Auditing;

use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\ActionEventListenerAggregate;
use Prooph\Common\Event\DetachAggregateHandlers;
use Prooph\Common\Messaging\DomainMessage;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\ServiceBus\MessageBus;
use Psr\Log\LoggerInterface;

final class CommandAuditor implements ActionEventListenerAggregate
{
    use DetachAggregateHandlers;

    /** @var LoggerInterface */
    private $logger;

    /** @var MessageConverter */
    private $messageConverter;

    /** @var array */
    private $meta;

    /**
     * @param LoggerInterface $logger
     * @param MessageConverter $messageConverter
     * @param array $meta May contain user id, or even ip address.
     */
    public function __construct(LoggerInterface $logger, MessageConverter $messageConverter, array $meta)
    {
        $this->logger = $logger;
        $this->messageConverter = $messageConverter;
        $this->meta = $meta;
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

        $data = $this->extractCommandData($event);
        $data['success'] = true;
        $this->logger->info(json_encode($data));
    }

    /**
     * @param ActionEvent $event
     */
    public function onErrorCommand(ActionEvent $event)
    {
        $exception = $event->getParam(MessageBus::EVENT_PARAM_EXCEPTION);
        $data = $this->extractCommandData($event);
        $data['success'] = false;

        if ($exception instanceof \Exception) {
            $data['exception'] = [
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        $this->logger->error(json_encode($data));
    }

    /**
     * @param ActionEvent $event
     * @return array
     */
    private function extractCommandData($event)
    {
        $command = $event->getParam(MessageBus::EVENT_PARAM_MESSAGE);
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
}
