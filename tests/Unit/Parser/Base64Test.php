<?php

namespace Flat3\Lodata\Tests\Unit\Parser;

use Flat3\Lodata\Helper\Base64;
use Flat3\Lodata\Tests\TestCase;

class Base64Test extends TestCase
{
    public function assertEncodesDecodes($from, $to)
    {
        $res = (new Base64($from))->encode()->get();
        $this->assertEquals($to, $res);

        $res = (new Base64($to))->decode()->get();
        $this->assertEquals($from, $res);
    }

    public function test_1()
    {
        $this->assertEncodesDecodes('teegh874v*!&@^&£@&^^&$*7xt-0', 'dGVlZ2g4NzR2KiEmQF4mwqNAJl5eJiQqN3h0LTA=');
    }

    public function test_2()
    {
        $this->assertEncodesDecodes('', '');
    }

    public function test_3()
    {
        $this->assertEncodesDecodes('a', 'YQ==');
    }

    public function test_4()
    {
        $this->assertEncodesDecodes('b', 'Yg==');
    }

    public function test_5()
    {
        $this->assertEncodesDecodes('test', 'dGVzdA==');
    }

    public function test_6()
    {
        $this->assertEncodesDecodes('text', 'dGV4dA==');
    }

    public function test_7()
    {
        $this->assertEncodesDecodes('+++', 'Kysr');
    }

    public function test_8()
    {
        $this->assertEncodesDecodes("\0", 'AA==');
    }

    public function test_9()
    {
        $bin = '6o5J+C1IYHc1ibno53+GAFcdR//5PG03R6pUmG9AeYDRypCHI4xVfeJm+Jif2TEFoP1+9MLHtx5Evkor9bM//ZTNFs0Z9ReZgivNl9YcrG1a7vU25FLF4QJ0AvaJMBIt/+d5OSDT3y260pibdIjUxEUDElgV+qfmXCG1ItlO0xUjpsbVz46W6Ql2Q3iyKh0XMhxYXwUTPjssNvXOUn7GqCtxwVSq6NQ+SZatXj+1jCRJBCggYIsp5p+kw7JSnL45BpxLCBGEu5OubrGuDqVcxOQ3eI3bcxNKSIzvh8/Q5sUfzZ0v1k3GRrc8uCTSjz9U5kyssfeIKdpdUx3Va4DQq9+c+4D5ng13QIRAiXtLCAUbO7VZs5dnnG+UuGvOGT62RBmgOJrjVuG8X7m16SOZowdPg3d/BoPXCvVRNtZ92Z7FJDnOsIGxz1h+220Fxa9QhJ7Zu0GvxP88lTYG0DwybZS5BmU5vzCMrcqH4PyhETz2J1+d3QbKyP9dWmmrJN72eVS9Jg43oqYyzVIQxOF61NGvCvZknHc0nQ13N+pLjMUt76CVaK1rvl0ftKk1JUFi8XWleXcLKnit92nGNE0RonQRxSvNEf4RHDkoVvG33RtmJnpG4z4jJmObUDe+IuqkP2wV0mKUuluyhJWcD3pL7eaD3+JK0qm/YjyWDE/X0yU=';
        $this->assertEncodesDecodes(base64_decode($bin), $bin);
    }
}