<pre>
								     /      \
							  	  \  \  ,,  /  /
								   '-.`\()/`.-'
								  .--_'(  )'_--.
							     / /` /`""`\ `\ \
			 					  |  |  ><  |  |
								  \  \      /  /
 									  '.__.'
			
								  Simplon/Spider
</pre>

-------------------------------------------------

[![Build Status](https://travis-ci.org/fightbulc/simplon_spider.svg?branch=master)](https://travis-ci.org/fightbulc/simplon_spider)

# Introduction To Simplon

### What is simplon/spider?

Spider parses a given ```html document``` and aggregates all essential data:
   - title
   - description
   - keywords
   - all h1 contents
   - open-graph tags
   - twitter tags
   - all images

It basically gives you sort of the same response as Facebook's scraper.
However, Facebook's scraper does not give you all essential data.
  
#### Facebook scraper response:
 
```json
{
   "og_object":{
      "id":"379786107965",
      "description":"Find the latest breaking news and information on the top stories, weather, business, entertainment, politics, and more. For in-depth coverage, CNN provides special reports, video, audio, photo galleries, and interactive guides",
      "title":"Breaking News, U.S., World, Weather, Entertainment & Video News - CNN.com",
      "type":"website",
      "updated_time":"2015-09-01T13:15:53+0000",
      "url":"http:\/\/www.cnn.com\/"
   },
   "share":{
      "comment_count":0,
      "share_count":1340555
   },
   "id":"http:\/\/cnn.com"
}
```

#### Spider response:

```json
{
   "title":"Breaking News, U.S., World, Weather, Entertainment & Video News - CNN.com",
   "description":"Find the latest breaking news and information on the top stories, weather, business, entertainment, politics, and more. For in-depth coverage, CNN provides special reports, video, audio, photo galleries, and interactive guides",
   "keywords":"breaking news, news online, U.S. news, world news, developing story, news video, CNN news, weather, business, money, politics, law, technology, entertainment, education, travel, health, special reports, autos, CNN TV",
   "url": "http:\/\/www.cnn.com\/",
   "images":[
      "http://i2.cdn.turner.com/cnnnext/dam/assets/150901143136-budapest-migrant-protest-fists-large-169.jpg",
      "http://i2.cdn.turner.com/cnnnext/dam/assets/110902115913-gates-of-auschwitz-large-169.jpg"
   ],
   "openGraph":{
      "pubdate":"2014-02-24T14:45:54Z",
      "url":"http://www.cnn.com",
      "title":"Breaking News, U.S., World, Weather, Entertainment &amp; Video News - CNN.com",
      "description":"Find the latest breaking news and information on the top stories, weather, business, entertainment, politics, and more. For in-depth coverage, CNN provides special reports, video, audio, photo galleries, and interactive guides",
      "site_name":"CNN",
      "type":"website"
   },
   "twitter":{
      "card":"summary_large_image"
   }
}
```
	
### Any dependencies?

- PHP 5.4
- CURL

-------------------------------------------------

# Install

Easy install via composer. Still no idea what composer is? Inform yourself [here](http://getcomposer.org).

```json
{
    "require": {
        "simplon/spider": "*"
    }
}
```

-------------------------------------------------

# Examples

The following examples are straight forward and should not require any additional explaintation.

### Parse by fetching the page first

```php
use Simplon\Spider\Spider;

// fetch and parse
$data = Spider::fetchParse('http://cnn.com');

echo json_encode($data); // json encode result
```

### Parse by existing html

```php
use Simplon\Spider\Spider;

// page html
$html = '...';

// fetch and parse
$data = Spider::parse($html, 'http://cnn.com'); // URL is needed to rebuild absolute image paths

echo json_encode($data); // json encode result
```

### Result in both cases

```json
{
   "title":"Breaking News, U.S., World, Weather, Entertainment & Video News - CNN.com",
   "description":"Find the latest breaking news and information on the top stories, weather, business, entertainment, politics, and more. For in-depth coverage, CNN provides special reports, video, audio, photo galleries, and interactive guides",
   "keywords":"breaking news, news online, U.S. news, world news, developing story, news video, CNN news, weather, business, money, politics, law, technology, entertainment, education, travel, health, special reports, autos, CNN TV",
   "url": "http:\/\/www.cnn.com\/",
   "images":[
      "http://i2.cdn.turner.com/cnnnext/dam/assets/150901143136-budapest-migrant-protest-fists-large-169.jpg",
      "http://i2.cdn.turner.com/cnnnext/dam/assets/110902115913-gates-of-auschwitz-large-169.jpg"
   ],
   "openGraph":{
      "pubdate":"2014-02-24T14:45:54Z",
      "url":"http://www.cnn.com",
      "title":"Breaking News, U.S., World, Weather, Entertainment &amp; Video News - CNN.com",
      "description":"Find the latest breaking news and information on the top stories, weather, business, entertainment, politics, and more. For in-depth coverage, CNN provides special reports, video, audio, photo galleries, and interactive guides",
      "site_name":"CNN",
      "type":"website"
   },
   "twitter":{
      "card":"summary_large_image"
   }
}
```

-------------------------------------------------

# License
simplon/spider is freely distributable under the terms of the MIT license.

Copyright (c) 2015 Tino Ehrich ([tino@bigpun.me](mailto:tino@bigpun.me))

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
