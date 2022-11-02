<?php

namespace Sabre\VObject;


trait PHPUnitAssertions
{
    
    public function assertVObjectEqualsVObject($expected, $actual, $message = '')
    {
        $getObj = function ($input) {
            if (is_resource($input)) {
                $input = stream_get_contents($input);
            }
            if (is_string($input)) {
                $input = Reader::read($input);
            }
            if (!$input instanceof Component) {
                $this->fail('Input must be a string, stream or VObject component');
            }
            unset($input->PRODID);
            if ($input instanceof Component\VCalendar && 'GREGORIAN' === (string) $input->CALSCALE) {
                unset($input->CALSCALE);
            }

            return $input;
        };

        $expected = $getObj($expected)->serialize();
        $actual = $getObj($actual)->serialize();

                preg_match_all('|^([A-Z]+):\\*\\*ANY\\*\\*\r$|m', $expected, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $actual = preg_replace(
                '|^'.preg_quote($match[1], '|').':(.*)\r$|m',
                $match[1].':**ANY**'."\r",
                $actual
            );
        }

        $this->assertEquals(
            $expected,
            $actual,
            $message
        );
    }
}
