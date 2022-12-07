<?php

namespace Predis\Configuration\Option;

use InvalidArgumentException;
use Predis\Configuration\OptionInterface;
use Predis\Configuration\OptionsInterface;

class Modules implements OptionInterface
{
    public function filter(OptionsInterface $options, $value)
    {
        if (!in_array($value, $this->getDefault($options), true)){
            throw new InvalidArgumentException('Wrong module given');
        }
    }

    public function getDefault(OptionsInterface $options): array
    {
        return ['RedisBloom'];
    }
}
