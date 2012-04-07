<?php
/*
 * Copyright (c) 2012, Jonathan Chan <jchan@icebrg.us>
 *
 * Permission to use, copy, modify, and/or distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY
 * SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION
 * OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN
 * CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */
namespace Jyc\HttpLang;

use UnexpectedValueException;

class HttpLang
{
    /**
     * Parses the HTTP Accept-Language header.
     * @param $header string|null An Accept-Languages header that will be
     * parsed instead of the one sent if specified.
     * @return array An associative array, mapping languages in the header to
     * their q-factors, with q-factors in descending order. Languages are
     * changed to lower-case.
     */
    public static function languages($header = null)
    {
        $languages = array();

        if ($header === null) {
            $header = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }

        // Split into language pairs, ex. {'en', 'en-gb;q=0.8', 'de;q=0.7'}.
        $pairs = explode(',', $header);
        if ($pairs === false || $pairs === array()) {
            // Return the first element in the whitelist if possible, otherwise
            // return an empty array.
            return array();
        }

        // Split into subpairs, ex. {'en-gb', '0.8'}.
        foreach ($pairs as $pair) {
            // We don't need to use mb_strtolower because language tags are
            // ASCII - see RFC 2616 § 3.10.
            $pair = trim(strtolower($pair));
            $subpair = explode(';q=', $pair);
            switch(count($subpair)) {
            case 2:
                list($language, $q) = $subpair;
                $languages[$language] = (float)$q;
                break;
            // If no q-factor is specified, the HTTP spec tells us to assume 1.
            case 1:
                list($language) = $subpair;
                $languages[$language] = 1;
                break;
            default:
                throw new UnexpectedValueException("The HTTP_ACCEPT_LANGUAGE header is malformed.");
            }
        }

        // Sort by q-factors in ascending order, then reverse for ascending order.
        asort($languages);
        $languages = array_reverse($languages);

        return $languages;
    }

    /**
     * Negotiates the client's preferred language based on the Accept-Language
     * HTTP header. The qualifier will be decreased by 10% for partial matches,
     * a la http_negotiate_language from the pecl_http extension.
     * @param $whitelist array|null An array of languages that will act as a
     * whitelist if specified. If no language is parsed, the first element will
     * be used as the default. Assumed to be in lowercase.
     * @param $header string|null An Accept-Languages header that will be
     * parsed instead of the one sent if specified.
     * @see http_negotiate_language
     * @return string|null The most preferred language.
     */
    public static function negotiate($whitelist = null, $header = null)
    {
        $languages = self::languages($header);
        $baseLanguages = array();
        $bestLanguage = null;
        $bestQ = 0;

        if ($whitelist === null) {
            return $languages[0];
        }

        // Strip the region suffixes from each language ('gb', 'us') and append
        // them to the baseLanguages array, mapping to the full original language.
        foreach ($whitelist as $language) {
            $parts = explode('-', $language, 2);
            $baseLanguages[$parts[0]] = $language;
        }

        foreach ($languages as $language => $q) {
            // Get the parts of the language (ex. {'en', 'gb'} from 'en-GB') if
            // possible.
            $parts = explode('-', $language, 2);

            if (in_array($language, $whitelist) && $q > $bestQ) {
                $bestLanguage = $language;
                $bestQ = $q;
            } elseif (isset($baseLanguages[$parts[0]]) && $q - 0.1 > $bestQ) {
                $bestLanguage = $baseLanguages[$parts[0]];
                $bestQ = $q;
            }
        }

        // Use the first element in $whitelist as the default if no language
        // matched.
        if ($bestLanguage == null && count($whitelist) >= 1) {
            $bestLanguage = $whitelist[0];
        }

        return $bestLanguage;
    }
}
