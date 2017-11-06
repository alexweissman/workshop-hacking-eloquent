<?php

use PHPUnit\Framework\TestCase;
use UserFrosting\Support\Util\Util;

class UtilTest extends TestCase
{
    public function testStringMatchesSuccess()
    {
        $str = 'assets-raw/admin/assets/local/widgets/js/users.js';

        $patterns = [
            '^assets-raw',
            '^assets-raw/(.*)',
            '^api/owls',
            '^test/assets-raw'
        ];

        $matches = [];
        $this->assertTrue(Util::stringMatches($patterns, $str, $matches));

        $this->assertEquals([
            '^assets-raw' => [
                'assets-raw'
            ],
            '^assets-raw/(.*)' => [
                'assets-raw/admin/assets/local/widgets/js/users.js',
                'admin/assets/local/widgets/js/users.js'
            ]
        ], $matches);
    }

    public function testStringMatchesFail()
    {
        $str = 'admin/owls/voles';

        $patterns = [
            '^assets-raw',
            '^owls',
            '^api/owls'
        ];

        $this->assertFalse(Util::stringMatches($patterns, $str));
    }
}
