<?php

namespace pbierans\scoring;

Class ScoreDimensions {
    /**
     * @var float[]
     */
    protected $dimensions = [];

    public function get(string $dimension, float $default = 0.0): float {
        return $this->dimensions[$dimension] ?? $default;
    }

    public function set(string $dimension, float $value): self {
        $this->dimensions[$dimension] = $value;

        return $this;
    }

    public function add(string $dimension, float $value = 0.0): self {
        $this->set($dimension, $this->get($dimension) + $value);

        return $this;
    }
}
