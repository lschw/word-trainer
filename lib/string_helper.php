<?php

declare(strict_types=1);


/**
 * Converts accented characters in a string to ascii equivalents
 * based on https://dev.to/bdelespierre/convert-accentuated-character-to-their-ascii-equivalent-in-php-3kf1
 *
 * @param  string $str  String with accented characters
 * @return string  String with accented characters converted to their ascii equivalents
 */
function convert_accented_chars_to_ascii(string $str): string
{
    $str = htmlentities($str, ENT_NOQUOTES, "utf-8");
    $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
    return $str;
}


/**
 * Removes punctuation marks in string
 *
 * @param  string $str  String with punctuation marks
 * @return string  String with removed punctuation marks
 */
function remove_punctuation_chars(string $str): string
{
    $str = str_replace(str_split(":;,.!¡?¿؟-·~"), "", $str);
    return $str;
}


/**
 * Removes article from beginning of string
 *
 * @todo Extend list of articles for more languages
 *
 * @param  string $str  String in which article shall be removed
 * @param  string $lang  Language of string. Required to determine article
 * @return string  String with removed article
 */
function remove_article(string $str, string $lang): string
{
    $articles = [
        "de" => ["der", "die", "das"],
        "es" => ["el", "la", "los", "las"],
        "fr" => ["le", "la", "les"]
    ];
    if (!array_key_exists($lang, $articles) || strlen(trim($str)) == 0) {
        return $str;
    }
    $words = array_filter(array_map("trim", explode(" ", trim($str))));
    if (in_array(strtolower($words[0]), $articles[$lang])) {
        array_shift($words);
        return implode(" ", $words);
    }
    return $str;
}
