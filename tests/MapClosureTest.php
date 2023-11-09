<?php

class MapClosureTest extends PHPUnit\Framework\TestCase
{
    public function testHashingClosures()
    {
        $map = new \Ds\Map();
        $c = new class {
            public $closure;
            public function __construct()
            {
                $this->closure = function () {
                };
            }
        };

        $map->put(1, $c);
        $map->put($c, 2);

        $this->assertEquals($c, $map->get(1));
        $this->assertEquals(2, $map->get($c));

        $map->put(3, ['a', $c]);
        $map->put(['a', $c], 4);

        $this->assertEquals(['a', $c], $map->get(3));
        $this->assertEquals(4, $map->get(['a', $c]));
    }
}
