<?php

namespace pbierans\scoring\test;

use pbierans\scoring\ScoringEngine;
use PHPUnit\Framework\TestCase;

final class ScoringTest extends TestCase {

    public function test_parser() {
        $scoringEngine = new ScoringEngine([]);
        $this->assertEquals('$this->dimensions->set("a",2)', $scoringEngine->parseRule('dimension("a")=2'));
        $this->assertEquals('$this->dimensions->set("a",2)', $scoringEngine->parseRule('dim("a")=2'));
        $this->assertEquals('$this->dimensions->set("a",2)', $scoringEngine->parseRule('d("a")=2'));
        $this->assertEquals('$this->dimensions->set("a",2)', $scoringEngine->parseRule('     d("a")  =  2    '));
        $this->assertEquals('$this->dimensions->set("a",2)', $scoringEngine->parseRule('d(a)=2'));

        $this->assertEquals('$this->dimensions->add("a",2)', $scoringEngine->parseRule('d("a")+=2'));
        $this->assertEquals('$this->dimensions->add("a",2)', $scoringEngine->parseRule('d(a)+=2'));
        $this->assertEquals('$this->dimensions->add("a",2)', $scoringEngine->parseRule('d(a) += 2'));
        $this->assertEquals('$this->dimensions->add("a",2)', $scoringEngine->parseRule('a:2'));
        $this->assertEquals('$this->dimensions->add("a",2)', $scoringEngine->parseRule('a :    2'));

        $this->assertEquals('$this->equal(1,"2")', $scoringEngine->parseRule('eq(1,"2")'));
        $this->assertEquals('$this->notEqual(1,"2")', $scoringEngine->parseRule('neq(1,"2")'));
        $this->assertEquals('$this->gte(1,"2")', $scoringEngine->parseRule('gte(1,"2")'));
        $this->assertEquals('$this->lte(1,"2")', $scoringEngine->parseRule('lte(1,"2")'));
        $this->assertEquals('$this->gt(1,"2")', $scoringEngine->parseRule('gt(1,"2")'));
        $this->assertEquals('$this->lt(1,"2")', $scoringEngine->parseRule('lt(1,"2")'));

        $this->assertEquals('$this->dimensions->get("a")', $scoringEngine->parseRule('dimension["a"]'));
        $this->assertEquals('$this->dimensions->get("a")', $scoringEngine->parseRule('dim["a"]'));
        $this->assertEquals('$this->dimensions->get("a")', $scoringEngine->parseRule('d["a"]'));
        $this->assertEquals('$this->dimensions->get("a")', $scoringEngine->parseRule('d[a]'));

        $this->assertEquals('$values["a-13"]', $scoringEngine->parseRule('values["a-13"]'));
        $this->assertEquals('$values["a-13"]', $scoringEngine->parseRule('value["a-13"]'));
        $this->assertEquals('$values["a-13"]', $scoringEngine->parseRule('v["a-13"]'));
        $this->assertEquals('$values["a-13"]', $scoringEngine->parseRule('v[a-13]'));

        $this->assertEquals('$values["a-13"]', $scoringEngine->parseRule('answers["a-13"]'));
        $this->assertEquals('$values["a-13"]', $scoringEngine->parseRule('answer["a-13"]'));
        $this->assertEquals('$values["a-13"]', $scoringEngine->parseRule('a["a-13"]'));
        $this->assertEquals('$values["a-13"]', $scoringEngine->parseRule('a[a-13]'));
    }

    public function test_parser_badchars_detection() {
        $scoringEngine = new ScoringEngine([]);
        $this->assertEquals("ö", $scoringEngine->badCharsInRule('Döner'));
    }

    public function test_dim_static() {
        $definition = ['d("a")=2', 'd("a")+=2', 'a:2'];
        $scoringEngine = new ScoringEngine($definition);
        $scoringEngine->calculate([]);
        $dimensions = $scoringEngine->dimensions;
        $this->assertEquals(6, $dimensions->get("a"));
    }

    public function test_answers_1() {
        $definition = ['d("a")=v[wert]'];
        $scoringEngine = new ScoringEngine($definition);
        $scoringEngine->calculate(["wert" => 13]);
        $dimensions = $scoringEngine->dimensions;
        $this->assertEquals(13, $dimensions->get("a"));
    }

    public function test_answers_2() {
        $definition = ['test : v[f1] + v[f2] + v[f3] + v[f4] + v[f5]'];
        $scoringEngine = new ScoringEngine($definition);
        $scoringEngine->calculate(["f1" => 1, "f2" => 2, "f3" => 3, "f4" => 4, "f5" => 5]);
        $dimensions = $scoringEngine->dimensions;
        $this->assertEquals(1 + 2 + 3 + 4 + 5, $dimensions->get("test"));
    }

    public function test_answers_3() {
        $definition = [
            'test : v[f1] + v[f2] + v[f3]',
            'test : v[f4] + v[f5]',
        ];
        $scoringEngine = new ScoringEngine($definition);
        $scoringEngine->calculate(["f1" => 1, "f2" => 2, "f3" => 3, "f4" => 4, "f5" => 5]);
        $dimensions = $scoringEngine->dimensions;
        $this->assertEquals(1 + 2 + 3 + 4 + 5, $dimensions->get("test"));
    }

    public function test_answers_and_dim_1() {
        $definition = [
            'test : v[f1] + v[f2] + v[f3]',
            'd(test) = d[test] * 3',
        ];
        $scoringEngine = new ScoringEngine($definition);
        $scoringEngine->calculate(["f1" => 1, "f2" => 2, "f3" => 3, "f4" => 4, "f5" => 5]);
        $dimensions = $scoringEngine->dimensions;
        $this->assertEquals((1 + 2 + 3) * 3, $dimensions->get("test"));
    }

    public function test_answers_and_dim_2() {
        $definition = [
            'testa : v[f1] + v[f2] + v[f3]',
            'testb : v[f4] + v[f5]',
            'testc : d[testa] * 3 + 4 * d[testb]',
        ];
        $scoringEngine = new ScoringEngine($definition);
        $scoringEngine->calculate(["f1" => 1, "f2" => 2, "f3" => 3, "f4" => 4, "f5" => 5]);
        $dimensions = $scoringEngine->dimensions;
        $this->assertEquals((1 + 2 + 3) * 3 + 4 * (4 + 5), $dimensions->get("testc"));
    }

    public function test_answers_and_percent() {
        $definition = [
            'testa : v[f1] + v[f2] + v[f3]',
            'testb : v[f4] + v[f5]',
            'testc : round(100 / 80 * ( d[testa] * 3 + 4 * d[testb]))',
        ];
        $scoringEngine = new ScoringEngine($definition);
        $scoringEngine->calculate(["f1" => 1, "f2" => 2, "f3" => 3, "f4" => 4, "f5" => 5]);
        $dimensions = $scoringEngine->dimensions;
        $this->assertEquals(round(100 / 80 * ((1 + 2 + 3) * 3 + 4 * (4 + 5))), $dimensions->get("testc"));
    }

    public function test_comparator_1() {
        $definition = [
            'test : (v[f1]==1)?3:0',
        ];
        $scoringEngine = new ScoringEngine($definition);
        $scoringEngine->calculate(["f1" => 1, "f2" => 2, "f3" => 3, "f4" => 4, "f5" => 5]);
        $dimensions = $scoringEngine->dimensions;
        $this->assertEquals(3, $dimensions->get("test"));
    }

    public function test_comparator_2() {
        $definition = [
            'test : eq(v[f2],2)*3 + eq(v[f4],2)*4 + eq(v[f5],5)',
        ];
        $scoringEngine = new ScoringEngine($definition);
        $scoringEngine->calculate(["f1" => 1, "f2" => 2, "f3" => 3, "f4" => 4, "f5" => 5]);
        $dimensions = $scoringEngine->dimensions;
        $this->assertEquals(4, $dimensions->get("test"));
    }

    public function test_comparator_and_dim() {
        $definition = [
            'test : eq(v[f2],2)*3 + eq(v[f4],2)*4 + eq(v[f5],5)',
            'test : eq(d[test],4)*2',
        ];
        $scoringEngine = new ScoringEngine($definition);
        $scoringEngine->calculate(["f1" => 1, "f2" => 2, "f3" => 3, "f4" => 4, "f5" => 5]);
        $dimensions = $scoringEngine->dimensions;
        $this->assertEquals(6, $dimensions->get("test"));
    }

    public function test_extended_comparators() {
        $definition = [
            'test : gte(v[f1],1)*1 + lte(v[f2],3)*2 + gt(v[f3],2)*3',
            'test : lt(v[f4],5)*4 + neq(d[test],7)*7',
        ];
        $scoringEngine = new ScoringEngine($definition);
        $scoringEngine->calculate(["f1" => 1, "f2" => 2, "f3" => 3, "f4" => 4, "f5" => 5]);
        $dimensions = $scoringEngine->dimensions;
        $this->assertEquals(17, $dimensions->get("test"));
    }
}
