<?php
/*
 * This file is part of the legalweb/psb-command-auditing package.
 * (c) 2015-2016, Legalwebb UK LTD
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\ServiceBus\Plugin\Auditing;

use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\DefaultActionEvent;
use Prooph\Common\Event\ListenerHandler;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\ServiceBus\MessageBus;
use Prooph\ServiceBus\Plugin\Auditing\CommandAuditor;
use Prooph\ServiceBus\Plugin\Auditing\MessageSerializer;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

class CommandAuditorTest extends \PHPUnit_Framework_TestCase
{
    /** @var LoggerInterface */
    private $logger;

    /** @var NoOpMessageConverter */
    private $messageSerializer;

    /** @var CommandAuditor */
    private $SUT;

    protected function setUp()
    {
        parent::setUp();

        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->messageSerializer = $this->prophesize(MessageSerializer::class);

        $this->SUT = new CommandAuditor(
            $this->logger->reveal(),
            $this->messageSerializer->reveal()
        );
    }

    /**
     * @test
     */
    public function it_will_attach_itself_to_event_emitter()
    {
        /** @var ActionEventEmitter $emitter */
        $emitter = $this->prophesize(ActionEventEmitter::class);

        $listener = $this->prophesize(ListenerHandler::class);

        $emitter
            ->attachListener(MessageBus::EVENT_FINALIZE, Argument::any())
            ->willReturn($listener)
            ->shouldBeCalled();

        $emitter
            ->attachListener(MessageBus::EVENT_HANDLE_ERROR, Argument::any())
            ->willReturn($listener)
            ->shouldBeCalled();

        $this->SUT->attach($emitter->reveal());
    }

    /**
     * @test
     */
    public function it_will_log_a_successful_command_call()
    {
        $this->logger->info(Argument::type('string'))->shouldBeCalled();
        $event = new DefaultActionEvent('test');

        $this->SUT->onFinalizeCommand($event);
    }

    /**
     * @test
     */
    public function it_will_log_an_error_on_command_call()
    {
        $this->logger->error(Argument::type('string'))->shouldBeCalled();
        $event = new DefaultActionEvent('test');

        $this->SUT->onErrorCommand($event);
    }

    /**
     * @test
     */
    public function it_will_skip_handling_success_if_exception_exists_on_event()
    {
        $this->logger->info(Argument::any())->shouldNotBeCalled();

        $event = new DefaultActionEvent('test');
        $event->setParam(MessageBus::EVENT_PARAM_EXCEPTION, new \Exception);

        $this->SUT->onFinalizeCommand($event);
    }
}
