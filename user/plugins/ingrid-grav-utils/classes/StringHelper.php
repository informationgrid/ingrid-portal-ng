<?php

namespace Grav\Plugin;

class StringHelper
{

    public static function convertUrlInText(string $text): string
    {
        $replaceText = "";
        $regex = "/((((ftp|https?):\/\/)|(w{3}.))[A-zÀ-ž0-9-_@:%+.~#?,&\\/=]+)[^ ,.)!\n\r\t\"]/";
        preg_match_all($regex, $text, $matches, PREG_OFFSET_CAPTURE);
        $replaceUrl = "";
        $startIndex = 0;
        $lastIndex = strlen($text);
        $findUrls = [];
        foreach($matches as $matchItems) {
            foreach ($matchItems as $match) {
                $matchUrl = $match[0];
                $matchIndex = $match[1];
                if (substr_count($matchUrl, "(") == substr_count($matchUrl, ")")) {
                    $subText = substr($text, $startIndex, $matchIndex - $startIndex);
                    if (!str_ends_with($subText, "=\"")) {
                        $replaceText .= $subText;
                        $replaceText .= "##" . $matchUrl . "##";
                        $startIndex = $matchIndex + strlen($matchUrl);
                    } else {
                        $replaceText .= $subText;
                        $replaceText .= $matchUrl;
                        $startIndex = $matchIndex + strlen($matchUrl);
                    }
                    $findUrls[] = $matchUrl;
                }
            }
            break;
        }
        $replaceText .= substr($text, $startIndex, $lastIndex - $startIndex);
        foreach ($findUrls as $findUrl) {
            $urlString = $findUrl;
            if (str_starts_with($findUrl, 'www.')) {
                $urlString = "https://" . $findUrl;
            }
            $replaceText = str_replace("##" . $findUrl . "##", '<a class="intext" target="_blank" href="' . $urlString . '" title="' . $findUrl . '">' . $findUrl . '</a>', $replaceText);

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