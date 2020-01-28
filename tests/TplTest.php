<?php

/*
 * Copyright (c) 2018 François Kooman <fkooman@tuxed.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace fkooman\Template\Tests;

use fkooman\Template\Tpl;
use PHPUnit\Framework\TestCase;

class TplTest extends TestCase
{
    public function testSimple()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ]
        );

        $this->assertSame(
            'value',
            trim($tpl->render('tpl1', ['key' => 'value']))
        );
    }

    public function testDateFormat()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ]
        );

        $this->assertSame(
            '2018-01-01',
            trim($tpl->render('tpl14', ['d' => '2018-01-01 09:00:00']))
        );
    }

    public function testEscaping()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ]
        );

        $this->assertSame(
            '&lt;/body&gt;',
            trim($tpl->render('tpl1', ['key' => '</body>']))
        );
    }

    public function testLayout()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ]
        );

        $this->assertSame(
            '<html>Foo</html>',
            trim($tpl->render('tpl2', []))
        );
    }

    public function testLayoutWithTemplateVars()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ]
        );

        $this->assertSame(
            '<html>barFoo</html>',
            trim($tpl->render('tpl3', []))
        );
    }

    public function testTranslation()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ],
            [
                __DIR__.'/locale/1',
            ]
        );
        $tpl->setLanguage('nl-NL');
        $this->assertSame(
            'Tekst',
            trim($tpl->render('tpl4', []))
        );
    }

    public function testTranslationUnsupportedLanguage()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ],
            [
                __DIR__.'/locale/1',
            ]
        );
        $tpl->setLanguage('foo-BAR');
        $this->assertSame(
            'Text',
            trim($tpl->render('tpl4', []))
        );
    }

    public function testTranslationCorruptLanguage()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ],
            [
                __DIR__.'/locale/1',
            ]
        );
        $tpl->setLanguage('../../etc/passwd');
        $this->assertSame(
            'Text',
            trim($tpl->render('tpl4', []))
        );
    }

    public function testMissingTranslation()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ],
            [
                __DIR__.'/locale/1',
            ]
        );
        $tpl->setLanguage('nl-NL');
        $this->assertSame(
            'Missing',
            trim($tpl->render('tpl5', []))
        );
    }

    public function testTranslationVariableSubstitution()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ],
            [
                __DIR__.'/locale/1',
            ]
        );
        $tpl->setLanguage('nl-NL');
        $this->assertSame(
            'Hallo foo!',
            trim($tpl->render('tpl6', ['userId' => 'foo']))
        );
    }

    public function testTranslationEscaping()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ],
            [
                __DIR__.'/locale/1',
            ]
        );
        $tpl->setLanguage('nl-NL');
        $this->assertSame(
            'Hallo &lt;/body&gt;!',
            trim($tpl->render('tpl6', ['userId' => '</body>']))
        );
    }

    public function testStringTrim()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ]
        );

        $this->assertSame(
            'This&hellip;med',
            trim($tpl->render('tpl20', ['key' => 'ThisIsASomewhatLongerStringThatWillBeTrimmed']))
        );
    }

    public function testMTranslationMultipleTranslationFiles()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ],
            [
                __DIR__.'/locale/1',
                __DIR__.'/locale/2',
            ]
        );
        $tpl->setLanguage('nl-NL');
        $this->assertSame(
            'Meer Tekst',
            trim($tpl->render('tpl15', []))
        );
    }

    public function testThemeLayout()
    {
        // tpl7 points to layout1, which is available both in the "tpl/theme1"
        // and "tpl" directory, but because "tpl/theme1" is specified first,
        // for all templates (and layouts) that are there, they are taken from
        // that folder... if it is missing from the first folder, the next
        // folder is used...
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
                __DIR__.'/tpl/theme1',
            ]
        );

        $this->assertSame(
            '<html><body>Foo</body></html>',
            trim($tpl->render('tpl7', []))
        );
    }

    public function testEscapeFunctions()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ]
        );

        $this->assertSame(
            'VALUE',
            trim($tpl->render('tpl8', ['key' => 'value']))
        );
    }

    public function testEscapeFunctionRegisteredCallback()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ]
        );
        $tpl->addCallback('my_strrev', function ($v) { return strrev($v); });

        $this->assertSame(
            'EULAV',
            trim($tpl->render('tpl9', ['key' => 'value']))
        );
    }

    public function testEscapeEncode()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ]
        );
        $this->assertSame(
            'foo%2Bbar',
            trim($tpl->render('tpl10', ['key' => 'foo+bar']))
        );
    }

    public function testExists()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ]
        );
        $this->assertSame(
            'template "tpl11" exists!template "bar11" does NOT exist!',
            trim($tpl->render('tpl11', []))
        );
    }

    public function testInsert()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ]
        );
        $this->assertSame(
            'YES!',
            trim($tpl->render('tpl12', []))
        );
    }

    public function testBatch()
    {
        $tpl = new Tpl(
            [
                __DIR__.'/tpl',
            ]
        );
        $this->assertSame(
            "foo<br />\nbar&amp;",
            trim($tpl->render('tpl13', ['foo' => "foo\nbar&"]))
        );
    }
}
