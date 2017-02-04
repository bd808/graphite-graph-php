<?php

namespace Graphite;

class IniParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Given: non-conforming for php ini file
     * Expect: a php error to be raised
     *
     * @expectedException \PHPUnit_Framework_Error
     */
    public function test_error_when_no_substitute()
    {
        // our test file contains illegal values when no substition has been done
        $iniFile = $this->iniPath('test_ini_parser_sub.ini');
        $ini = IniParser::parse($iniFile);
    }

    /**
     * Given: non-conforming for php ini file
     * When: `@` suppression is used
     * Expect: no php error and false return value
     */
    public function test_suppress_load_error_returns_false()
    {
        $iniFile = $this->iniPath('test_ini_parser_sub.ini');
        $ini = @IniParser::parse($iniFile);
        $this->assertFalse($ini, 'Suppressed load error returns false');
    }

    /**
     * Given: an ini file with variables and empty replacements
     * Expect: variables to be returned verbatum
     */
    public function test_default_substitution()
    {
        $iniFile = $this->iniPath('test_ini_parser_sub.ini');
        $expect = [
            'KEY1' => 'VALUE1',
            'KEY2' => 'VALUE2',
            'SECTION1' => [
                'partial_KEY3' => ' has whitespace ',
                'KEY3_partial' => '{funky}',
                'escape' => '{{KEY1}}',
                'escape2' => 'a{b}c{{d}}e',
                'escape3' => '{{KEY1}}',
                'escape4' => '{{KEY1}}',
            ],
        ];

        $ini = IniParser::parse($iniFile, []);
        $this->assertSame($expect, $ini);
    }

    /**
     * Given: an ini file with variables and replacements
     * Expect: variables to be replaced
     */
    public function test_substitution()
    {
        $iniFile = $this->iniPath('test_ini_parser_sub.ini');
        $expect = [
            'k1' => 'v1',
            'k2' => 'v2',
            's1' => [
                'partial_k3' => 'hw',
                'k3_partial' => '{f}',
                'escape' => '{{KEY1}}',
                'escape2' => 'funkier',
                'escape3' => '{{KEY1}}',
                'escape4' => '{{KEY1}}',
            ],
        ];

        $vars = [
            'KEY1' => 'k1',
            'VALUE1' => 'v1',
            'KEY2' => 'k2',
            'VALUE2' => 'v2',
            'KEY3' => 'k3',
            'SECTION1' => 's1',
            ' has whitespace ' => 'hw',
            'funky' => 'f',
            'a{b}c{{d}}e' => 'funkier',
        ];

        $ini = IniParser::parse($iniFile, $vars);
        $this->assertSame($expect, $ini);
    }

    /**
     * Given: an ini file with boolean values
     * Expect: consistent results
     */
    public function test_boolean_repr()
    {
        $iniFile = $this->iniPath('test_boolean_repr.ini');
        $expect = [
            'truthy' => [
                'a' => '1',
                'b' => '1',
                'c' => '1',
                'd' => '1',
                'e' => '1',
            ],

            'falsey' => [
                'a' => '',
                'b' => '0',
                'c' => '',
                'd' => '',
                'e' => '',
                'f' => '',
            ],
        ];

        $ini = IniParser::parse($iniFile);
        $this->assertSame($expect, $ini);
    }

    /**
     * Get the path to an ini file.
     * @param string $file File name
     * @return string Path to file
     */
    protected function iniPath($file)
    {
        return __DIR__ . DIRECTORY_SEPARATOR . $file;
    }
}
