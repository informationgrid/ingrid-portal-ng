<?php

namespace Grav\Plugin;

class StringHelper
{

    public static function convertUrlInText(string $text): string
    {
        $replaceText = $text;
        $regex = "/((((ftp|https?):\/\/)|(w{3}.))[A-zÀ-ž0-9-_@:%+.~#?,&\\/=]+)[^ ,.]/";
        preg_match_all($regex, $replaceText, $matches, PREG_SET_ORDER);
        $replaceUrl = "";
        foreach($matches as $matchItems) {
            foreach ($matchItems as $match) {
                if (substr_count($match, "(") == substr_count($match, ")")) {
                    $replaceUrl = $match;
                    break;
                }
            }
            $urlString = $replaceUrl;
            if (str_starts_with($replaceUrl, 'www.')) {
                $urlString = "https://" . $urlString;
            }
            $replaceText = str_replace($replaceUrl, '<a class="intext" target="_blank" href="' . $urlString . '" title="' . $urlString . '">' . $urlString . '</a>', $replaceText);
        }
        return $replaceText;
    }

    public static function containsLetters(string $string): bool
    {
        if (empty($string)) {
            return false;
        }
        if(preg_match('@[a-zA-Z]@', $string) > 0){
            return true;
        }
        return false;
    }

    public static function containsOnlyLetters(string $string): bool
    {
        if (empty($string)) {
            return false;
        }
        if(preg_match('@[0-9]@', $string)){
            return false;
        }
        return true;
    }
}