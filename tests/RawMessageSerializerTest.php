<?php

namespace ProophTest\ServiceBus\Plugin\Auditing;

use Prooph\Common\Messaging\DomainMessage;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\ServiceBus\Plugin\Auditing\RawMessageSerializer;

class RawMessageSerializerTest extends \PHPUnit_Framework_TestCase
{
    /** @var MessageConverter */
    private $messageConverter;

    /** @var RawMessageSerializer */
    private $SUT;

    protected function setUp()
    {
        parent::setUp();

        $this->messageConverter = $this->prophesize(MessageConverter::class);

        $this->SUT = new RawMessageSerializer($this->messageConverter->reveal(), ['ip' => '127.0.0.1']);
    }

    /**
     * @test
     */
    public function it_will_return_exception_details()
    {
        $exception = new \Exception($message = 'Hey', $code = 10001);
        $serialized = $this->SUT->serializeException($exception);

        $this->assertArrayHasKey('type', $serialized);
        $this->assertArrayHasKey('message', $serialized);
        $this->assertArrayHasKey('code', $serialized);
        $this->assertArrayHasKey('file', $serialized);
        $this->assertArrayHasKey('line', $serialized);

        $this->assertEquals(get_class($exception), $serialized['type']);
        $this->assertEquals($code, $serialized['code']);
        $this->assertEquals($message, $serialized['message']);
    }

    /**
     * @test
     */
    public function it_will_use_message_converter_for_domain_messages()
    {
        $message = $this->prophesize(DomainMessage::class);
        $this->messageConverter->convertToArray($message)->willReturn(['abc' => '123']);

        $response = $this->SUT->serializeCommand($message->reveal());

        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('abc', $response['data']);
    }

    /**
     * @test
     */
    public function it_will_use_get_class_name_for_plain_objects()
    {
        $response = $this->SUT->serializeCommand(new \stdClass);

        $this->assertArrayHasKey('command', $response);
        $this->assertEquals('stdClass', $response['command']);
    }

    /**
     * @test
     */
    public function it_will_use_default_command_value_if_not_an_object()
    {
        $response = $this->SUT->serializeCommand('abc');

        $this->assertArrayHasKey('command', $response);
        $this->assertEquals('abc', $response['command']);
    }

    /**
     * @test
     */
    public function it_will_add_meta_to_serialized_command()
    {
        $response = $this->SUT->serializeCommand('abc');

        $this->assertArrayHasKey('meta', $response);
        $this->assertArrayHasKey('ip', $response['meta']);
        $this->assertEquals('127.0.0.1', $response['meta']['ip']);
    }
}
