<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Test classes for handling embedded media (audio/video).
 *
 * @package core_media
 * @copyright 2012 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/medialib.php');

/**
 * Test script for media embedding.
 */
class medialib_testcase extends advanced_testcase {

    /** @var array Files covered by test */
    public static $includecoverage = array('lib/medialib.php', 'lib/outputrenderers.php');

    /**
     * Pre-test setup. Preserves $CFG.
     */
    public function setUp() {
        global $CFG;
        parent::setUp();

        // Reset CFG.
        $this->resetAfterTest(true);

        // Consistent initial setup: all players disabled.
        $CFG->core_media_enable_html5video = false;
        $CFG->core_media_enable_html5audio = false;
        $CFG->core_media_enable_mp3 = false;
        $CFG->core_media_enable_flv = false;
        $CFG->core_media_enable_wmp = false;
        $CFG->core_media_enable_qt = false;
        $CFG->core_media_enable_rm = false;
        $CFG->core_media_enable_youtube = false;
        $CFG->core_media_enable_vimeo = false;
        $CFG->core_media_enable_swf = false;

        // Strict headers turned off.
        $CFG->xmlstrictheaders = false;

        $_SERVER = array('HTTP_USER_AGENT' => '');
        $this->pretend_to_be_safari();
    }

    /**
     * Sets user agent to Safari.
     */
    private function pretend_to_be_safari() {
        // Pretend to be using Safari browser (must support mp4 for tests to work).
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_8; de-at) ' .
                'AppleWebKit/533.21.1 (KHTML, like Gecko) Version/5.0.5 Safari/533.21.1';
    }

    /**
     * Sets user agent to Firefox.
     */
    private function pretend_to_be_firefox() {
        // Pretend to be using Firefox browser (must support ogg for tests to work).
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
    }

    /**
     * Post-test cleanup. Replaces old $CFG.
     */
    public function tearDown() {
        // Replace original user agent.
        if (isset($this->realserver)) {
            $_SERVER = $this->realserver;
        }

        parent::tearDown();
    }

    /**
     * Test for the core_media_player is_enabled.
     */
    public function test_is_enabled() {
        global $CFG;

        // Test enabled: unset, 0, 1.
        $test = new core_media_player_test;
        $this->assertFalse($test->is_enabled());
        $CFG->core_media_enable_test = 0;
        $this->assertFalse($test->is_enabled());
        $CFG->core_media_enable_test = 1;
        $this->assertTrue($test->is_enabled());
    }

    /**
     * Test for the core_media_player list_supported_urls.
     */
    public function test_list_supported_urls() {
        global $CFG;
        $test = new core_media_player_test;

        // Some example URLs.
        $supported1 = new moodle_url('http://example.org/1.test');
        $supported2 = new moodle_url('http://example.org/2.TST');
        $unsupported = new moodle_url('http://example.org/2.jpg');

        // No URLs => none.
        $result = $test->list_supported_urls(array());
        $this->assertEquals(array(), $result);

        // One supported URL => same.
        $result = $test->list_supported_urls(array($supported1));
        $this->assertEquals(array($supported1), $result);

        // Two supported URLS => same.
        $result = $test->list_supported_urls(array($supported1, $supported2));
        $this->assertEquals(array($supported1, $supported2), $result);

        // One unsupported => none.
        $result = $test->list_supported_urls(array($unsupported));
        $this->assertEquals(array(), $result);

        // Two supported and one unsupported => same.
        $result = $test->list_supported_urls(array($supported2, $unsupported, $supported1));
        $this->assertEquals(array($supported2, $supported1), $result);
    }

    /**
     * Test for core_media_renderer get_players
     */
    public function test_get_players() {
        global $CFG, $PAGE;

        // All players are initially disabled (except link, which you can't).
        $renderer = new core_media_renderer_test($PAGE, '');
        $this->assertEquals('link', $renderer->get_players_test());

        // A couple enabled, check the order.
        $CFG->core_media_enable_html5audio = true;
        $CFG->core_media_enable_mp3 = true;
        $renderer = new core_media_renderer_test($PAGE, '');
        $this->assertEquals('mp3, html5audio, link', $renderer->get_players_test());
    }

    /**
     * Test for core_media_renderer can_embed_url
     */
    public function test_can_embed_url() {
        global $CFG, $PAGE;

        // All players are initially disabled, so mp4 cannot be rendered.
        $url = new moodle_url('http://example.org/test.mp4');
        $renderer = new core_media_renderer_test($PAGE, '');
        $this->assertFalse($renderer->can_embed_url($url));

        // Enable QT player.
        $CFG->core_media_enable_qt = true;
        $renderer = new core_media_renderer_test($PAGE, '');
        $this->assertTrue($renderer->can_embed_url($url));

        // QT + html5.
        $CFG->core_media_enable_html5video = true;
        $renderer = new core_media_renderer_test($PAGE, '');
        $this->assertTrue($renderer->can_embed_url($url));

        // Only html5.
        $CFG->core_media_enable_qt = false;
        $renderer = new core_media_renderer_test($PAGE, '');
        $this->assertTrue($renderer->can_embed_url($url));

        // Only WMP.
        $CFG->core_media_enable_html5video = false;
        $CFG->core_media_enable_wmp = true;
        $renderer = new core_media_renderer_test($PAGE, '');
        $this->assertFalse($renderer->can_embed_url($url));
    }

    /**
     * Test for core_media_renderer embed_url.
     * Checks multiple format/fallback support.
     */
    public function test_embed_url_fallbacks() {
        global $CFG, $PAGE;

        $url = new moodle_url('http://example.org/test.mp4');

        // All plugins disabled, NOLINK option.
        $renderer = new core_media_renderer_test($PAGE, '');
        $t = $renderer->embed_url($url, 0, 0, '',
                array(core_media::OPTION_NO_LINK => true));
        // Completely empty.
        $this->assertEquals('', $t);

        // All plugins disabled but not NOLINK.
        $renderer = new core_media_renderer_test($PAGE, '');
        $t = $renderer->embed_url($url);
        $this->assert_contents(false, false, true, $t);

        // HTML5 plugin enabled.
        $CFG->core_media_enable_html5video = true;
        $renderer = new core_media_renderer_test($PAGE, '');
        $t = $renderer->embed_url($url);
        $this->assert_contents(false, true, true, $t);

        // QT plugin enabled as well.
        $CFG->core_media_enable_qt = true;
        $renderer = new core_media_renderer_test($PAGE, '');
        $t = $renderer->embed_url($url);
        $this->assert_contents(true, true, true, $t);

        // Nolink turned off, plugins still enabled.
        $t = $renderer->embed_url($url, 0, 0, '',
                array(core_media::OPTION_NO_LINK => true));
        $this->assert_contents(true, true, false, $t);
    }

    /**
     * Checks the contents of the resulting HTML code to ensure it used the
     * correct embed method(s).
     *
     * @param bool $hasqt True if it should have QT embed code
     * @param bool $hashtml5 True if it should have HTML5 embed code
     * @param bool $haslink True if it should have a fallback link
     * @param string $text HTML content
     */
    private function assert_contents($hasqt, $hashtml5, $haslink, $text) {
        // I tried to avoid making it specific to the exact details of the html
        // code, picking out only single key strings that would let it check
        // whether final code contains the right things.
        $qt = 'qtplugin.cab';
        $html5 = '</video>';
        $link = 'mediafallbacklink';

        $this->assertEquals($hasqt, self::str_contains($text, $qt));
        $this->assertEquals($hashtml5, self::str_contains($text, $html5));
        $this->assertEquals($haslink, self::str_contains($text, $link));
    }

    /**
     * Test for core_media_renderer embed_url.
     * Check SWF works including the special option required to enable it
     */
    public function test_embed_url_swf() {
        global $CFG, $PAGE;
        $CFG->core_media_enable_swf = true;
        $renderer = new core_media_renderer_test($PAGE, '');

        // Without any options...
        $url = new moodle_url('http://example.org/test.swf');
        $t = $renderer->embed_url($url);
        $this->assertFalse(self::str_contains($t, '</object>'));

        // ...and with the 'no it's safe, I checked it' option.
        $url = new moodle_url('http://example.org/test.swf');
        $t = $renderer->embed_url($url, '', 0, 0, array(core_media::OPTION_TRUSTED => true));
        $this->assertTrue(self::str_contains($t, '</object>'));
    }

    /**
     * Test for core_media_renderer embed_url.
     * Exercises all the basic formats not covered elsewhere.
     */
    public function test_embed_url_other_formats() {
        global $CFG, $PAGE;

        // Enable all players and get renderer.
        $CFG->core_media_enable_html5audio = true;
        $CFG->core_media_enable_mp3 = true;
        $CFG->core_media_enable_flv = true;
        $CFG->core_media_enable_wmp = true;
        $CFG->core_media_enable_rm = true;
        $CFG->core_media_enable_youtube = true;
        $CFG->core_media_enable_vimeo = true;
        $renderer = new core_media_renderer_test($PAGE, '');

        // Check each format one at a time. This is a basic check to be sure
        // the HTML is included for files of the right type, not a test that
        // the HTML itself is correct.

        // Format: mp3.
        $url = new moodle_url('http://example.org/test.mp3');
        $t = $renderer->embed_url($url);
        $this->assertTrue(self::str_contains($t, 'core_media_mp3_'));

        // Format: flv.
        $url = new moodle_url('http://example.org/test.flv');
        $t = $renderer->embed_url($url);
        $this->assertTrue(self::str_contains($t, 'core_media_flv_'));

        // Format: wmp.
        $url = new moodle_url('http://example.org/test.avi');
        $t = $renderer->embed_url($url);
        $this->assertTrue(self::str_contains($t, '6BF52A52-394A-11d3-B153-00C04F79FAA6'));

        // Format: rm.
        $url = new moodle_url('http://example.org/test.rm');
        $t = $renderer->embed_url($url);
        $this->assertTrue(self::str_contains($t, 'CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA'));

        // Format: youtube.
        $url = new moodle_url('http://www.youtube.com/watch?v=vyrwMmsufJc');
        $t = $renderer->embed_url($url);
        $this->assertTrue(self::str_contains($t, '</iframe>'));
        $url = new moodle_url('http://www.youtube.com/v/vyrwMmsufJc');
        $t = $renderer->embed_url($url);
        $this->assertTrue(self::str_contains($t, '</iframe>'));

        // Format: youtube playlist.
        $url = new moodle_url('http://www.youtube.com/view_play_list?p=PL6E18E2927047B662');
        $t = $renderer->embed_url($url);
        $this->assertTrue(self::str_contains($t, '</object>'));
        $url = new moodle_url('http://www.youtube.com/playlist?list=PL6E18E2927047B662');
        $t = $renderer->embed_url($url);
        $this->assertTrue(self::str_contains($t, '</object>'));
        $url = new moodle_url('http://www.youtube.com/p/PL6E18E2927047B662');
        $t = $renderer->embed_url($url);
        $this->assertTrue(self::str_contains($t, '</object>'));

        // Format: vimeo.
        $url = new moodle_url('http://vimeo.com/1176321');
        $t = $renderer->embed_url($url);
        $this->assertTrue(self::str_contains($t, '</iframe>'));

        // Format: html5audio.
        $this->pretend_to_be_firefox();
        $url = new moodle_url('http://example.org/test.ogg');
        $t = $renderer->embed_url($url);
        $this->assertTrue(self::str_contains($t, '</audio>'));
    }

    /**
     * Test for core_media_renderer embed_url.
     * Checks the EMBED_OR_BLANK option.
     */
    public function test_embed_or_blank() {
        global $CFG, $PAGE;
        $CFG->core_media_enable_html5audio = true;
        $this->pretend_to_be_firefox();

        $renderer = new core_media_renderer_test($PAGE, '');

        $options = array(core_media::OPTION_FALLBACK_TO_BLANK => true);

        // Embed that does match something should still include the link too.
        $url = new moodle_url('http://example.org/test.ogg');
        $t = $renderer->embed_url($url, '', 0, 0, $options);
        $this->assertTrue(self::str_contains($t, '</audio>'));
        $this->assertTrue(self::str_contains($t, 'mediafallbacklink'));

        // Embed that doesn't match something should be totally blank.
        $url = new moodle_url('http://example.org/test.mp4');
        $t = $renderer->embed_url($url, '', 0, 0, $options);
        $this->assertEquals('', $t);
    }

    /**
     * Test for core_media_renderer embed_url.
     * Checks that size is passed through correctly to player objects and tests
     * size support in html5video output.
     */
    public function test_embed_url_size() {
        global $CFG, $PAGE;

        // Technically this could break in every format and they handle size
        // in several different ways, but I'm too lazy to test it in every
        // format, so let's just pick one to check the values get passed
        // through.
        $CFG->core_media_enable_html5video = true;
        $renderer = new core_media_renderer_test($PAGE, '');
        $url = new moodle_url('http://example.org/test.mp4');

        // HTML5 default size - specifies core width and does not specify height.
        $t = $renderer->embed_url($url);
        $this->assertTrue(self::str_contains($t, 'width="' . CORE_MEDIA_VIDEO_WIDTH . '"'));
        $this->assertFalse(self::str_contains($t, 'height'));

        // HTML5 specified size - specifies both.
        $t = $renderer->embed_url($url, '', '666', '101');
        $this->assertTrue(self::str_contains($t, 'width="666"'));
        $this->assertTrue(self::str_contains($t, 'height="101"'));

        // HTML5 size specified in url, overrides call.
        $url = new moodle_url('http://example.org/test.mp4?d=123x456');
        $t = $renderer->embed_url($url, '', '666', '101');
        $this->assertTrue(self::str_contains($t, 'width="123"'));
        $this->assertTrue(self::str_contains($t, 'height="456"'));
    }

    /**
     * Test for core_media_renderer embed_url.
     * Checks that name is passed through correctly to player objects and tests
     * name support in html5video output.
     */
    public function test_embed_url_name() {
        global $CFG, $PAGE;

        // As for size this could break in every format but I'm only testing
        // html5video.
        $CFG->core_media_enable_html5video = true;
        $renderer = new core_media_renderer_test($PAGE, '');
        $url = new moodle_url('http://example.org/test.mp4');

        // HTML5 default name - use filename.
        $t = $renderer->embed_url($url);
        $this->assertTrue(self::str_contains($t, 'title="test.mp4"'));

        // HTML5 specified name - check escaping.
        $t = $renderer->embed_url($url, 'frog & toad');
        $this->assertTrue(self::str_contains($t, 'title="frog &amp; toad"'));
    }

    /**
     * Test for core_media_renderer split_alternatives.
     */
    public function test_split_alternatives() {
        // Single URL - identical moodle_url.
        $mp4 = 'http://example.org/test.mp4';
        $result = core_media::split_alternatives($mp4, $w, $h);
        $this->assertEquals($mp4, $result[0]->out(false));

        // Width and height weren't specified.
        $this->assertEquals(0, $w);
        $this->assertEquals(0, $h);

        // Two URLs - identical moodle_urls.
        $webm = 'http://example.org/test.webm';
        $result = core_media::split_alternatives("$mp4#$webm", $w, $h);
        $this->assertEquals($mp4, $result[0]->out(false));
        $this->assertEquals($webm, $result[1]->out(false));

        // Two URLs plus dimensions.
        $size = 'd=400x280';
        $result = core_media::split_alternatives("$mp4#$webm#$size", $w, $h);
        $this->assertEquals($mp4, $result[0]->out(false));
        $this->assertEquals($webm, $result[1]->out(false));
        $this->assertEquals(400, $w);
        $this->assertEquals(280, $h);

        // Two URLs plus legacy dimensions (use last one).
        $result = core_media::split_alternatives("$mp4?d=1x1#$webm?$size", $w, $h);
        $this->assertEquals($mp4, $result[0]->out(false));
        $this->assertEquals($webm, $result[1]->out(false));
        $this->assertEquals(400, $w);
        $this->assertEquals(280, $h);
    }

    /**
     * Test for core_media_renderer embed_alternatives (with multiple urls)
     */
    public function test_embed_alternatives() {
        global $PAGE, $CFG;

        // Most aspects of this are same as single player so let's just try
        // a single typical / complicated scenario.

        // MP3, WebM and FLV.
        $urls = array(
            new moodle_url('http://example.org/test.mp4'),
            new moodle_url('http://example.org/test.webm'),
            new moodle_url('http://example.org/test.flv'),
        );

        // Enable html5 and flv.
        $CFG->core_media_enable_html5video = true;
        $CFG->core_media_enable_flv = true;
        $renderer = new core_media_renderer_test($PAGE, '');

        // Result should contain HTML5 with two sources + FLV.
        $t = $renderer->embed_alternatives($urls);

        // HTML5 sources - mp4, not flv or webm (not supported in Safari).
        $this->assertTrue(self::str_contains($t, '<source src="http://example.org/test.mp4"'));
        $this->assertFalse(self::str_contains($t, '<source src="http://example.org/test.webm"'));
        $this->assertFalse(self::str_contains($t, '<source src="http://example.org/test.flv"'));

        // FLV is before the video tag (indicating html5 is used as fallback to flv
        // and not vice versa).
        $this->assertTrue((bool)preg_match('~core_media_flv_.*<video~s', $t));

        // Do same test with firefox and check we get the webm and not mp4.
        $this->pretend_to_be_firefox();
        $t = $renderer->embed_alternatives($urls);

        // HTML5 sources - webm, not not flv or mp4 (not supported in Firefox).
        $this->assertFalse(self::str_contains($t, '<source src="http://example.org/test.mp4"'));
        $this->assertTrue(self::str_contains($t, '<source src="http://example.org/test.webm"'));
        $this->assertFalse(self::str_contains($t, '<source src="http://example.org/test.flv"'));
    }

    /**
     * Checks if text contains a given string. Should be PHP built-in function,
     * but oddly isn't.
     *
     * @param string $text Text
     * @param string $value Value that should be contained within the text
     * @return bool True if value is contained
     */
    public static function str_contains($text, $value) {
        return strpos($text, $value) !== false;
    }

    /**
     * Converts moodle_url array into a single comma-separated string for
     * easier testing.
     *
     * @param array $urls Array of moodle_urls
     * @return string String containing those URLs, comma-separated
     */
    public static function string_urls($urls) {
        $out = array();
        foreach ($urls as $url) {
            $out[] = $url->out(false);
        }
        return implode(',', $out);
    }

    /**
     * Converts associative array into a semicolon-separated string for easier
     * testing.
     *
     * @param array $options Associative array
     * @return string String of form 'a=b;c=d'
     */
    public static function string_options($options) {
        $out = '';
        foreach ($options as $key => $value) {
            if ($out) {
                $out .= ';';
            }
            $out .= "$key=$value";
        }
        return $out;
    }
}

/**
 * Media player stub for testing purposes.
 */
class core_media_player_test extends core_media_player {
    /** @var array Array of supported extensions */
    public $ext;
    /** @var int Player rank */
    public $rank;
    /** @var int Arbitrary number */
    public $num;

    /**
     * @param int $num Number (used in output)
     * @param int $rank Player rank
     * @param array $ext Array of supported extensions
     */
    public function __construct($num = 1, $rank = 13, $ext = array('tst', 'test')) {
        $this->ext = $ext;
        $this->rank = $rank;
        $this->num = $num;
    }

    public function embed($urls, $name, $width, $height, $options) {
        return $this->num . ':' . medialib_test::string_urls($urls) .
                ",$name,$width,$height,<!--FALLBACK-->," . medialib_test::string_options($options);
    }

    public function get_supported_extensions() {
        return $this->ext;
    }

    public function get_rank() {
        return $this->rank;
    }
}

/**
 * Media renderer override for testing purposes.
 */
class core_media_renderer_test extends core_media_renderer {
    /**
     * Access list of players as string, shortening it by getting rid of
     * repeated text.
     * @return string Comma-separated list of players
     */
    public function get_players_test() {
        $players = $this->get_players();
        $out = '';
        foreach ($players as $player) {
            if ($out) {
                $out .= ', ';
            }
            $out .= str_replace('core_media_player_', '', get_class($player));
        }
        return $out;
    }
}
