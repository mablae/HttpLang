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
namespace jyc\httplang;

require_once __DIR__ . '/autoload.php';

use PHPUnit_Framework_TestCase;

class HttpLangTest extends PHPUnit_Framework_TestCase
{
    public function testLanguages()
    {
        $languages = HttpLang::languages('da, en-gb;q=0.8, en;q=0.7');
        $this->assertEquals(array('da'=>1, 'en-gb'=>0.8, 'en'=>0.7), $languages);
    }

    public function testPreferredLanguage()
    {
        $header = 'fr;q=0.6,de;q=0.5,en-gb;q=0.8';

        $this->assertEquals('en-us',
            HttpLang::negotiate(array('en-us', 'de', 'fr'), $header));
        $this->assertEquals('fr',
            HttpLang::negotiate(array('de', 'fr'), $header));
    }
}
