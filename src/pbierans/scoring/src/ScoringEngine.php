<?php

namespace pbierans\scoring;

class ScoringEngine {
    /**
     * @var array
     */
    private $definition;
    /**
     * @var \pbierans\scoring\ScoreDimensions
     */
    public $dimensions;

    /**
     * ScoringEngine constructor.
     *
     * @param array $definition
     */
    public function __construct(array $definition = []) {
        $this->definition = $definition;
        $this->dimensions = new ScoreDimensions();
    }

    public function calculate(array $values) {
        foreach ($this->definition as $rule) {
            $badchars = $this->badCharsInRule($rule);
            if ($badchars !== "") {
                echo "input: " . $rule . "\n";
                die ("error: " . $rule . "bad chars: " . $badchars);
            }
            $parsed = $this->parseRule($rule);
            eval($parsed . ";");
        }
    }

    public function parseRule(string $ruleRaw) {
        // erst mal aufräumen
        $rule = trim($ruleRaw, " ;\r\n");
        $rule = preg_replace("/[\r\n\t ]+/", " ", $rule);

        // unnötige whitespaces raus, das macht die regexp einfacherer
        $rule = preg_replace("/\s?([=+\-\\\*:()])\s?/", '$1', $rule);

        // Aliase
        $rule = str_replace("dim(", "d(", $rule);
        $rule = str_replace("dimension(", "d(", $rule);
        $rule = str_replace("dim[", "d[", $rule);
        $rule = str_replace("dimension[", "d[", $rule);
        $rule = str_replace("dimensions[", "d[", $rule);
        $rule = str_replace("values[", "v[", $rule);
        $rule = str_replace("value[", "v[", $rule);
        $rule = str_replace("answers[", "a[", $rule);
        $rule = str_replace("answer[", "a[", $rule);

        $rule = preg_replace("/neq\(([^,\)]+),([^,\)]+)\)/", '$this->notEqual($1,$2)', $rule);
        $rule = preg_replace("/eq\(([^,\)]+),([^,\)]+)\)/", '$this->equal($1,$2)', $rule);
        $rule = preg_replace("/gte\(([^,\)]+),([^,\)]+)\)/", '$this->gte($1,$2)', $rule);
        $rule = preg_replace("/lte\(([^,\)]+),([^,\)]+)\)/", '$this->lte($1,$2)', $rule);
        $rule = preg_replace("/gt\(([^,\)]+),([^,\)]+)\)/", '$this->gt($1,$2)', $rule);
        $rule = preg_replace("/lt\(([^,\)]+),([^,\)]+)\)/", '$this->lt($1,$2)', $rule);

        // Syntax zum Bearbeiten von Dimensionen
        $rule = preg_replace("/d\(\"([^)\"]+)\"\)=([^;]+)/", '$this->dimensions->set("$1",$2)', $rule);
        $rule = preg_replace("/d\(([^)\"]+)\)=([^;]+)/", '$this->dimensions->set("$1",$2)', $rule);
        $rule = preg_replace("/d\(\"([^)\"]+)\"\)\+=([^;]+)/", '$this->dimensions->add("$1",$2)', $rule);
        $rule = preg_replace("/d\(([^)]+)\)\+=([^;]+)/", '$this->dimensions->add("$1",$2)', $rule);
        $rule = preg_replace("/^([a-z]+):([^;]+)/", '$this->dimensions->add("$1",$2)', $rule);
        $rule = preg_replace("/d\[\"([^]\"]+)\"\]/", '$this->dimensions->get("$1")', $rule);
        $rule = preg_replace("/d\[([^]\"]+)\]/", '$this->dimensions->get("$1")', $rule);

        // Antworten lesen
        $rule = preg_replace("/v\[\"([^]\"]+)\"\]/", '$values["$1"]', $rule);
        $rule = preg_replace("/v\[([^]\"]+)\]/", '$values["$1"]', $rule);

        $rule = preg_replace("/a\[\"([^]\"]+)\"\]/", '$values["$1"]', $rule);
        $rule = preg_replace("/a\[([^]\"]+)\]/", '$values["$1"]', $rule);

        echo "input: " . $ruleRaw . "\nevals: " . $rule . "\n\n";

        return $rule;
    }

    public function badCharsInRule(string $rule): string {
        $rule = trim($rule, " ;\r\n");

        return preg_replace('/[a-zA-Z0-9.#=()"+:\[\] *\/\?,]/', "", $rule);
    }

    public function equal($a, $b): int {
        return ($a == $b) ? 1 : 0;
    }

    public function notEqual($a, $b): int {
        return ($a != $b) ? 1 : 0;
    }

    public function gte($a, $b): int {
        return ($a >= $b) ? 1 : 0;
    }

    public function gt($a, $b): int {
        return ($a > $b) ? 1 : 0;
    }

    public function lte($a, $b): int {
        return ($a <= $b) ? 1 : 0;
    }

    public function lt($a, $b): int {
        return ($a < $b) ? 1 : 0;
    }
}
