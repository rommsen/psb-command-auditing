<?php
/*
 * This file is part of the legalweb/psb-command-auditing package.
 * (c) 2015-2016, Legalwebb UK LTD
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\ServiceBus\Plugin\Auditing;

use Prooph\ServiceBus\Plugin\Auditing\MessageSerializer;
use Prooph\ServiceBus\Plugin\Auditing\SecretMessageSerializer;

class SecretMessageSerializerTest extends \PHPUnit_Framework_TestCase
{
    /** @var MessageSerializer */
    private $messageSerializer;

    /** @var SecretMessageSerializer */
    private $SUT;

    protected function setUp()
    {
        parent::setUp();

        $this->messageSerializer = $this->prophesize(MessageSerializer::class);

        $this->SUT = new SecretMessageSerializer($this->messageSerializer->reveal(), ['password'], '[SECRET]');
    }

    /**
     * @test
     */
    public function it_will_return_exception_details_straight_away()
    {
        $exception = new \Exception;
        $expected = ['abc' => '123'];
        $this->messageSerializer->serializeException($exception)->willReturn($expected);

        $actual = $this->SUT->serializeException($exception);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_will_return_obscure_values_for_items_in_secret_array()
    {
        $command = 'string_command';
        $expected = ['password' => '[SECRET]', 'abc' => '123', 'numeric_array' => ['foo', 'bar']];

        $this->messageSerializer->serializeCommand($command)->willReturn([
            'password' => '123',
            'abc' => '123',
            'numeric_array' => [
                'foo',
                'bar'
            ]
        ]);

        $actual = $this->SUT->serializeCommand($command);

        $this->assertEquals($expected, $actual);
    }
}
