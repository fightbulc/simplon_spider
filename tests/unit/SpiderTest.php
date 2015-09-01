<?php

namespace Tests\unit;

use Simplon\Spider\Spider;

/**
 * Class SpiderTest
 * @package Tests
 */
class SpiderTest extends \PHPUnit_Framework_TestCase
{
    public function testParse()
    {
        // load html
        $html = file_get_contents(__DIR__ . '/../template.html');

        // parse html
        $data = Spider::parse($html, 'http://foo.com');

        // test defaults
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals('THIS IS A TITLE', $data['title']);

        $this->assertArrayHasKey('description', $data);
        $this->assertEquals('THIS IS A DESCRIPTION', $data['description']);

        $this->assertArrayHasKey('keywords', $data);
        $this->assertEquals('THESE ARE KEYWORDS', $data['keywords']);

        // test open graph
        $this->assertArrayHasKey('open-graph', $data);

        $this->assertArrayHasKey('type', $data['open-graph']);
        $this->assertEquals('WEBSITE', $data['open-graph']['type']);

        $this->assertArrayHasKey('title', $data['open-graph']);
        $this->assertEquals('THIS IS A OG:TITLE', $data['open-graph']['title']);

        $this->assertArrayHasKey('description', $data['open-graph']);
        $this->assertEquals('THIS IS A OG:DESCRIPTION', $data['open-graph']['description']);

        $this->assertArrayHasKey('image', $data['open-graph']);
        $this->assertEquals('http://foo.com/bar.png', $data['open-graph']['image']);

        $this->assertArrayHasKey('url', $data['open-graph']);
        $this->assertEquals('http://foo.com', $data['open-graph']['url']);

        // test twitter
        $this->assertArrayHasKey('twitter', $data);

        $this->assertArrayHasKey('card', $data['twitter']);
        $this->assertEquals('summary', $data['twitter']['card']);

        $this->assertArrayHasKey('site', $data['twitter']);
        $this->assertEquals('@flickr', $data['twitter']['site']);

        $this->assertArrayHasKey('title', $data['twitter']);
        $this->assertEquals('Small Island Developing States Photo Submission', $data['twitter']['title']);

        $this->assertArrayHasKey('description', $data['twitter']);
        $this->assertEquals('View the album on Flickr.', $data['twitter']['description']);

        $this->assertArrayHasKey('image', $data['twitter']);
        $this->assertEquals('https://farm6.staticflickr.com/5510/14338202952_93595258ff_z.jpg', $data['twitter']['image']);

        // test images
        $this->assertArrayHasKey('images', $data);
        $this->assertContains('http://foo.com/bar.png', $data['images']);
        $this->assertContains('https://farm6.staticflickr.com/5510/14338202952_93595258ff_z.jpg', $data['images']);
        $this->assertContains('http://foo.com/foobar.png', $data['images']);
        $this->assertContains('http://foo.com/sizzle.png', $data['images']);
    }
}