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
