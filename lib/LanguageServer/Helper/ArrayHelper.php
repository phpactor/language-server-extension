<?php

namespace Phpactor\Extension\LanguageServer\Helper;

final class ArrayHelper
{
    public static function flatten(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $key = $prefix.$key;

            $flattenedResult = \is_array($value)
                ? self::flatten($value, "$key.")
                : [$key => $value]
            ;

            $result = \array_merge($result, $flattenedResult);
        }

        return $result;
    }
}
