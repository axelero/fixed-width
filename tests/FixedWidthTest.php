<?php

namespace Axelero\FixedWidth;

use RuntimeException;

class FixedWidthTest extends \PHPUnit_Framework_TestCase
{
    public function testNoType()
    {
        $this->setExpectedException(RuntimeException::class);

        $config = [
            'first_name' => [],
            'start'      => 1,
            'end'        => 10,
        ];
        $obj = new FixedWidth($config);
    }

    public function testInvalidType()
    {
        $this->setExpectedException(RuntimeException::class);

        $config = [
            'first_name' => [
                'type'  => 'xxxxx',
                'start' => 1,
                'end'   => 10,
            ],
        ];
        $obj = new FixedWidth($config);
    }

    public function testInvalidPadding()
    {
        $this->setExpectedException(RuntimeException::class);

        $config = [
            'first_name' => [
                'type'    => 'string',
                'start'   => 1,
                'end'     => 10,
                'padding' => '',
            ],
        ];
        $obj = new FixedWidth($config);
    }

    public function testConstructor()
    {
        $config = [
            'first_name' => [
                'type'  => 'string',
                'start' => 1,
                'end'   => 10,
            ],
            'last_name' => [
                'type'  => 'string',
                'start' => 11,
                'end'   => 20,
            ],
        ];
        $obj = new FixedWidth($config);
        $this->assertSame(count($config), count($obj->fields()));
    }

    public function testEndLengthMismatch()
    {
        $this->setExpectedException(RuntimeException::class);

        $config = [
            'first_name' => [
                'type'   => 'string',
                'start'  => 1,
                'length' => 3,
                'end'    => 9,
            ],
        ];
        $obj = new FixedWidth($config);
    }

    public function testBadAlignment()
    {
        $this->setExpectedException(RuntimeException::class);

        $config = [
            'first_name' => [
                'type'      => 'string',
                'alignment' => 'xxxx',
                'start'     => 1,
                'end'       => 9,
            ],
        ];
        $obj = new FixedWidth($config);
    }

    public function testDefaults()
    {
        foreach (['string', 'integer'] as $type) {
            $config = [
                'xxx' => [
                    'type'  => $type,
                    'start' => 1,
                    'end'   => 10,
                ],
            ];
            $obj = new FixedWidth($config);
            $this->assertArrayHasKey('alignment', $obj->fields()['xxx']);
            $this->assertArrayHasKey('padding', $obj->fields()['xxx']);
            $this->assertArrayHasKey('default', $obj->fields()['xxx']);

            // test padding does not get overwritten
            $config = [
                'xxx' => [
                    'type'    => $type,
                    'start'   => 1,
                    'end'     => 10,
                    'padding' => '.',
                ],
            ];
            $obj = new FixedWidth($config);
            $this->assertSame('.', $obj->fields()['xxx']['padding']);
        }
    }

    public function testEndBeforeStart()
    {
        $this->setExpectedException(RuntimeException::class);

        $config = [
            'first_name' => [
                'type'  => 'string',
                'start' => 10,
                'end'   => 5,
            ],
        ];
        $obj = new FixedWidth($config);
    }

    public function testStartEndLength()
    {
        $config = [
            'xxx' => [
                'type'   => 'string',
                'start'  => 3,
                'length' => 4,
                'end'    => 6,
            ],
        ];
        $obj = new FixedWidth($config);
        $this->assertSame($obj->fields()['xxx']['start'], 3);
        $this->assertSame($obj->fields()['xxx']['length'], 4);
        $this->assertSame($obj->fields()['xxx']['end'], 6);

        $config = [
            'xxx' => [
                'type'  => 'string',
                'start' => 3,
                'end'   => 6,
            ],
        ];
        $obj = new FixedWidth($config);
        $this->assertSame($obj->fields()['xxx']['start'], 3);
        $this->assertSame($obj->fields()['xxx']['length'], 4);
        $this->assertSame($obj->fields()['xxx']['end'], 6);
    }

    public function testReadField()
    {
        $config = [
            'first_name' => [
                'type'   => 'string',
                'start'  => 3,
                'length' => 10,
            ],
        ];
        $obj  = new FixedWidth($config);
        $line = '12345678901234567890';
        $this->assertSame('3456789012', $obj->readField($line, 'first_name'));

        // when a line is smaller than the upper bound everything should still work
        $config = [
            'first_name' => [
                'type'   => 'string',
                'start'  => 15,
                'length' => 10,
            ],
        ];
        $obj  = new FixedWidth($config);
        $line = '12345678901234567890';
        $this->assertSame('567890', $obj->readField($line, 'first_name'));
        // check the newlines are discarded
        $this->assertSame('567890', $obj->readField($line . "\n", 'first_name'));
    }

    public function testReadLine()
    {
        $config = [
            'a' => [
                'type'   => 'string',
                'start'  => 3,
                'length' => 10,
            ],
            // numeric indexes will not be printed
            [
                'type'  => 'string',
                'start' => 13,
                'end'   => 13,
            ],
            'b' => [
                'type'  => 'string',
                'start' => 14,
                'end'   => 17,
            ],
        ];
        $obj  = new FixedWidth($config);
        $line = '12345678901234567890';
        $this->assertSame([
            'a' => '3456789012',
            'b' => '4567',
        ], $obj->readLine($line));

        $config = [
            'a' => [
                'type'  => 'string',
                'start' => 1,
                'end'   => 5,
            ],
            'b' => [
                'type'  => 'integer',
                'start' => 6,
                'end'   => 10,
            ],
        ];
        $obj  = new FixedWidth($config);
        $line = 'xxx  00004';
        $this->assertSame([
            'a' => 'xxx',
            'b' => 4,
        ], $obj->readLine($line));
    }

    public function testStringToText()
    {
        $config = [
            'a' => [
                'type'   => 'string',
                'start'  => 3,
                'length' => 10,
            ],
        ];
        $obj = new FixedWidth($config);

        // strings
        $this->assertSame('axelero   ', $obj->valueToText('a', 'axelero'));
        $this->assertSame('axeleroaxe', $obj->valueToText('a', 'axeleroaxelero'));
    }

    public function testTextToString()
    {
        $config = [
            'a' => [
                'type'   => 'string',
                'start'  => 3,
                'length' => 10,
            ],
        ];
        $obj   = new FixedWidth($config);
        $value = $obj->readField('  xxx          ', 'a');

        // strings
        $this->assertSame('xxx', $value);
    }

    public function testIntegersToText()
    {
        $config = [
            'a' => [
                'type'  => 'integer',
                'start' => 11,
                'end'   => 17,
            ],
        ];
        $obj = new FixedWidth($config);

        // numbers
        $this->assertSame('0000005', $obj->valueToText('a', 5));

        // numbers should not overflow
        $this->setExpectedException(RuntimeException::class);
        $obj->valueToText('a', 52398732982379832);
    }

    public function testNewlinesException()
    {
        $config = [
            'a' => [
                'type'   => 'string',
                'start'  => 1,
                'length' => 10,
            ],
        ];
        $obj = new FixedWidth($config);

        $this->setExpectedException(RuntimeException::class);
        $obj->valueToText('a', "xxx \n xxx");
    }

    public function testTextToInteger()
    {
        $config = [
            'a' => [
                'type'   => 'integer',
                'start'  => 3,
                'length' => 10,
            ],
        ];
        $obj   = new FixedWidth($config);
        $value = $obj->readField('  0000000090     ', 'a');

        // strings
        $this->assertSame(90, $value);
    }

    public function testOverlap()
    {
        $config = [
            'a' => [
                'type'  => 'string',
                'start' => 3,
                'end'   => 14,
            ],
            'b' => [
                'type'  => 'string',
                'start' => 10,
                'end'   => 17,
            ],
        ];
        $this->setExpectedException(RuntimeException::class);
        $obj = new FixedWidth($config);
    }

    public function testValidate()
    {
        $config = [
            'a' => [
                'type'  => 'string',
                'start' => 3,
                'end'   => 9,
            ],
            'b' => [
                'type'  => 'integer',
                'start' => 10,
                'end'   => 17,
            ],
        ];

        $obj = new FixedWidth($config);
        $this->assertTrue($obj->validate(['a' => 'xxx', 'b' => 1]));

        $this->setExpectedException(InvalidDataException::class);
        $this->assertTrue($obj->validate(['a' => 1, 'b' => 1]));
    }

    public function testGetLength()
    {
        $config = [
            'a' => [
                'type'  => 'string',
                'start' => 3,
                'end'   => 9,
            ],
            'b' => [
                'type'   => 'integer',
                'start'  => 10,
                'length' => 7,
            ],
        ];
        $obj = new FixedWidth($config);
        $this->assertSame(16, $obj->getLength());

        $obj = new FixedWidth([]);
        $this->assertSame(0, $obj->getLength());
    }
    public function testWriteLine()
    {
        $config = [
            'a' => [
                'type'  => 'string',
                'start' => 1,
                'end'   => 5,
            ],
            'b' => [
                'type'  => 'integer',
                'start' => 6,
                'end'   => 10,
            ],
        ];
        $data = ['a' => 'xxx', 'b' => 42];
        $obj  = new FixedWidth($config);
        $this->assertSame('xxx  00042', $obj->writeLine($data));

        $config = [
            'a' => [
                'type'  => 'string',
                'start' => 6,
                'end'   => 10,
            ],
            'b' => [
                'type'  => 'integer',
                'start' => 1,
                'end'   => 5,
            ],
        ];
        $data = ['a' => 'xxx', 'b' => 42];
        $obj  = new FixedWidth($config);
        $this->assertSame('00042xxx  ', $obj->writeLine($data));

        $config = [
            'a' => [
                'type'      => 'string',
                'start'     => 1,
                'end'       => 6,
                'padding'   => '.',
                'alignment' => 'right',
            ],
            'b' => [
                'type'    => 'string',
                'start'   => 7,
                'end'     => 9,
                'padding' => '.',
            ],
        ];
        $data = ['a' => 'xxx', 'b' => ''];
        $obj  = new FixedWidth($config);
        $this->assertSame('...xxx...', $obj->writeLine($data));
    }

    public function testMissingFields()
    {
        $config = [
            'a' => [
                'type'  => 'string',
                'start' => 1,
                'end'   => 5,
            ],
            'b' => [
                'type'  => 'integer',
                'start' => 6,
                'end'   => 10,
            ],
        ];
        $obj = new FixedWidth($config);
        $this->assertSame('     00000', $obj->writeLine([]));
    }

    public function testDefaultValues()
    {
        $config = [
            'a' => [
                'type'    => 'string',
                'start'   => 1,
                'end'     => 5,
                'default' => 'empty',
            ],
            'b' => [
                'type'    => 'integer',
                'start'   => 6,
                'end'     => 10,
                'default' => '42',
            ],
        ];
        $obj = new FixedWidth($config);
        $this->assertSame('empty00042', $obj->writeLine([]));
    }
}
