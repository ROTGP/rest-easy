<?php

namespace ROTGP\RestEasy\Traits;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Str;

trait ArrayUtilsTrait
{      
    protected function toCamelArray($arr)
    {
        for($i = 0; $i < sizeof($arr); $i++)
            $arr[$i] = Str::camel($arr[$i]);
        return $arr;
    }

    protected function toSnakeArray($arr)
    {
        if ($this->isAssociative($arr)) {
            $result = [];
            foreach($arr as $key => $value)
                $result[Str::snake($key)] = $value;
            return $result;
        }
        for($i = 0; $i < sizeof($arr); $i++)
            $arr[$i] = Str::snake($arr[$i]);
        return $arr;
    }

    protected function toStudlyArray($arr)
    {
        for($i = 0; $i < sizeof($arr); $i++)
            $arr[$i] = Str::studly($arr[$i]);
        return $arr;
    }

    protected function isAssociative(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
