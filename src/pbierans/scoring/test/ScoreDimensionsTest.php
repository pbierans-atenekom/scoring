<?php

namespace pbierans\scoring\test;

use pbierans\scoring\ScoreDimensions;
use PHPUnit\Framework\TestCase;

final class ScoreDimensionsTest extends TestCase {

    function test_initializing() {
        $sd = new ScoreDimensions();
        $this->assertEquals(0.0, $sd->get("a"));
    }

    function test_setter() {
        $sd = new ScoreDimensions();
        $sd->set("a", 3);
        $this->assertEquals(3.0, $sd->get("a"));
    }

    function test_set_and_add() {
        $sd = new ScoreDimensions();
        $sd->set("a", 3);
        $sd->add("a", 3);
        $this->assertEquals(6.0, $sd->get("a"));
    }

    function test_lazy() {
        $sd = new ScoreDimensions();
        $sd->add("a", 3);
        $this->assertEquals(3.0, $sd->get("a"));
    }

    function test_daisychaining() {
        $sd = new ScoreDimensions();
        $sd->set("a", 1)->add("a", 2);
        $sd->set("b", 2)->add("b", 1);
        $this->assertEquals(3.0, $sd->get("a"));
        $this->assertEquals(3.0, $sd->get("b"));
    }

    function test_scoping() {
        $sd1 = new ScoreDimensions();
        $sd2 = new ScoreDimensions();
        $sd1->set("a", 2);
        $sd2->set("a", 1);
        $this->assertEquals(2.0, $sd1->get("a"));
        $this->assertEquals(1.0, $sd2->get("a"));
    }
}
