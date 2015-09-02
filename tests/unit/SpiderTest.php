<?php

namespace Tests\unit;

use Simplon\Spider\Spider;
use Simplon\Spider\SpiderException;

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

        $this->assertArrayHasKey('url', $data);
        $this->assertEquals('http://foo.com', $data['url']);

        // test open graph
        $this->assertArrayHasKey('openGraph', $data);

        $this->assertArrayHasKey('type', $data['openGraph']);
        $this->assertEquals('WEBSITE', $data['openGraph']['type']);

        $this->assertArrayHasKey('title', $data['openGraph']);
        $this->assertEquals('THIS IS A OG:TITLE', $data['openGraph']['title']);

        $this->assertArrayHasKey('description', $data['openGraph']);
        $this->assertEquals('THIS IS A OG:DESCRIPTION', $data['openGraph']['description']);

        $this->assertArrayHasKey('image', $data['openGraph']);
        $this->assertEquals('http://foo.com/bar.png', $data['openGraph']['image']);

        $this->assertArrayHasKey('url', $data['openGraph']);
        $this->assertEquals('http://foo.com', $data['openGraph']['url']);

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
        $this->assertNotContains('http://foo.com/data:image/gif;base64,R0lGODlhEAAJAJEAAAAAAP///////wAAACH5BAEAAAIALAAAAAAQAAkAAAIKlI+py+0Po5yUFQA7', $data['images']);
    }

    public function testHttpException()
    {
        $this->setExpectedExceptionRegExp('Simplon\Spider\SpiderException', '/Requested page could not be retrieved. Received http code:/', SpiderException::HTTP_ERROR_CODE);
        Spider::fetchParse('https://pushcast.io/foobar/barfoo');
    }

    public function testRequestException()
    {
        $this->setExpectedExceptionRegExp('Simplon\Spider\SpiderException', '/Requested page could not be retrieved. Received message:/', SpiderException::REQUEST_ERROR_CODE);
        Spider::fetchParse('https://foooo-12313231313-johnny-bravo.com');
    }
}