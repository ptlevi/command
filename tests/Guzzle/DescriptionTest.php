<?php

namespace GuzzleHttp\Tests\Command\Guzzle;

use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\SchemaFormatter;

/**
 * @covers GuzzleHttp\Command\Guzzle\Description
 */
class DescriptionTest extends \PHPUnit_Framework_TestCase
{
    protected $operations;

    public function setup()
    {
        $this->operations = array(
            'test_command' => [
                'name'        => 'test_command',
                'description' => 'documentationForCommand',
                'httpMethod'  => 'DELETE',
                'class'       => 'Guzzle\\Tests\\Service\\Mock\\Command\\MockCommand',
                'parameters'  => array(
                    'bucket'  => array('required' => true),
                    'key'     => array('required' => true)
                )
            ]
        );
    }

    public function testConstructor()
    {
        $service = new Description(['operations' => $this->operations]);
        $this->assertEquals(1, count($service->getOperations()));
        $this->assertFalse($service->hasOperation('foobar'));
        $this->assertTrue($service->hasOperation('test_command'));
    }

    public function testContainsModels()
    {
        $d = new Description([
            'operations' => ['foo' => []],
            'models' => [
                'Tag'    => ['type' => 'object'],
                'Person' => ['type' => 'object']
            ]
        ]);
        $this->assertTrue($d->hasModel('Tag'));
        $this->assertTrue($d->hasModel('Person'));
        $this->assertFalse($d->hasModel('Foo'));
        $this->assertInstanceOf('GuzzleHttp\Command\Guzzle\Parameter', $d->getModel('Tag'));
        $this->assertEquals(['Tag', 'Person'], array_keys($d->getModels()));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRetrievingMissingModelThrowsException()
    {
        $d = new Description([]);
        $d->getModel('foo');
    }

    public function testHasAttributes()
    {
        $d = new Description(array(
            'operations'  => array(),
            'name'        => 'Name',
            'description' => 'Description',
            'apiVersion'  => '1.24'
        ));

        $this->assertEquals('Name', $d->getName());
        $this->assertEquals('Description', $d->getDescription());
        $this->assertEquals('1.24', $d->getApiVersion());
    }

    public function testPersistsCustomAttributes()
    {
        $data = [
            'operations'  => ['foo' => ['class' => 'foo', 'parameters' => []]],
            'name'        => 'Name',
            'description' => 'Test',
            'apiVersion'  => '1.24',
            'auth'        => 'foo',
            'keyParam'    => 'bar'
        ];
        $d = new Description($data);
        $this->assertEquals('foo', $d->getData('auth'));
        $this->assertEquals('bar', $d->getData('keyParam'));
        $this->assertEquals(['auth' => 'foo', 'keyParam' => 'bar'], $d->getData());
        $this->assertNull($d->getData('missing'));
    }

    public function testHasToArray()
    {
        $data = [
            'operations'  => [],
            'models'      => ['foo' => ['type' => 'string']],
            'name'        => 'Name',
            'description' => 'Test'
        ];
        $d = new Description($data);
        $d->getModel('foo');
        $arr = $d->toArray();
        $this->assertEquals('Name', $arr['name']);
        $this->assertEquals('Test', $arr['description']);
        $this->assertEquals(['foo' => ['type' => 'string', 'name' => 'foo']], $arr['models']);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptionForMissingOperation()
    {
        $s = new Description([]);
        $this->assertNull($s->getOperation('foo'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidatesOperationTypes()
    {
        $s = new Description(array(
            'operations' => array('foo' => new \stdClass())
        ));
    }

    public function testHasBaseUrl()
    {
        $description = new Description(['baseUrl' => 'http://foo.com']);
        $this->assertEquals('http://foo.com', $description->getBaseUrl());
    }

    public function testModelsHaveNames()
    {
        $desc = [
            'models' => [
                'date' => ['type' => 'string'],
                'user'=> [
                    'type' => 'object',
                    'properties' => [
                        'dob' => ['$ref' => 'date']
                    ]
                ]
            ]
        ];

        $s = new Description($desc);
        $this->assertEquals('date', $s->getModel('date')->getName());
        $this->assertEquals('dob', $s->getModel('user')->getProperty('dob')->getName());
    }

    public function testHasOperations()
    {
        $desc = ['operations' => ['foo' => ['parameters' => ['foo' => [
            'name' => 'foo'
        ]]]]];
        $s = new Description($desc);
        $this->assertInstanceOf('GuzzleHttp\\Command\\Guzzle\\Operation', $s->getOperation('foo'));
        $this->assertSame($s->getOperation('foo'), $s->getOperation('foo'));
        $this->assertEquals($desc, $s->toArray());
    }

    public function testHasFormatter()
    {
        $s = new Description([]);
        $this->assertNotEmpty($s->format('date', 'now'));
    }

    public function testCanUseCustomFormatter()
    {
        $formatter = $this->getMockBuilder('GuzzleHttp\\Common\\Guzzle\\SchemaFormatter')
            ->setMethods(['format'])
            ->getMock();
        $formatter->expects($this->once())
            ->method('format');
        $s = new Description([], ['formatter' => $formatter]);
        $s->format('time', 'now');
    }
}
