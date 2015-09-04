<?php

namespace Simplon\Spider;

use Simplon\Request\Request;
use Simplon\Request\RequestException;

/**
 * Class Spider
 * @package Simplon\Spider
 */
class Spider
{
    private static $lastUrl;

    /**
     * @param string $url
     *
     * @return array
     * @throws SpiderException
     */
    public static function fetchParse($url)
    {
        try
        {
            $response = Request::get($url, [], [CURLOPT_FOLLOWLOCATION => 1, CURLOPT_SSL_VERIFYPEER => false]);

            if ($response->getHttpCode() === 200)
            {
                $body = $response->getBody();

                // catch non-utf8 documents
                if (empty($response->getHeader()['content-type']) === false && strpos($response->getHeader()['content-type'], 'ISO-8859-1') !== false)
                {
                    $body = utf8_encode($body);
                }

                self::$lastUrl = $response->getLastUrl();

                return self::parse($body, $url);
            }

            $error = 'Requested page could not be retrieved. Received http code: ' . $response->getHttpCode();
            $code = SpiderException::HTTP_ERROR_CODE;
        }
        catch (RequestException $e)
        {
            $error = 'Requested page could not be retrieved. Received message: ' . $e->getMessage();
            $code = SpiderException::REQUEST_ERROR_CODE;
        }

        throw new SpiderException($error, $code);
    }

    /**
     * @param string $html
     * @param null|string $url
     *
     * @return array
     */
    public static function parse($html, $url = null)
    {
        // title and h1 header
        $data = self::parseClosedTags($html, ['title' => 'title', 'h1' => 'headlines']);

        if (isset($data['headlines']) && is_array($data['headlines']) === false)
        {
            $data['headlines'] = [$data['headlines']];
        }

        // --------------------------------------
        // add url

        if ($url !== null)
        {
            $data['url'] = $url;

            // lets rebuild the url in case we got a prior redirect to a file path
            if (self::$lastUrl !== null)
            {
                $components = parse_url(self::$lastUrl);
                $url = $components['scheme'] . '://' . $components['host'];
            }
        }

        // --------------------------------------
        // and now lets get meta etc.

        $parsedOpenTagElements = self::parseOpenTag($html, ['meta', 'img', 'link']);

        // --------------------------------------
        // add description and keywords

        $defaultMetas = self::aggregateDefaultMetas($parsedOpenTagElements);

        if ($defaultMetas !== null)
        {
            $data = array_merge($data, $defaultMetas);
        }

        // --------------------------------------
        // add all image data

        $data['images'] = self::aggregateImages($parsedOpenTagElements, $url);

        // --------------------------------------
        // handle twitter

        $twitter = self::aggregateTwitter($parsedOpenTagElements);

        if ($twitter !== null)
        {
            $data['twitter'] = $twitter;

            $addImage =
                empty($twitter['image']) === false
                && empty($data['images']) === false
                && in_array($twitter['image'], $data['images']) === false;

            if ($addImage)
            {
                array_unshift($data['images'], $twitter['image']);
            }
        }

        // --------------------------------------
        // handle facebook open graph

        $openGraph = self::aggregateOpenGraph($parsedOpenTagElements);

        if ($openGraph !== null)
        {
            $data['openGraph'] = $openGraph;

            $addImage =
                empty($openGraph['image']) === false
                && empty($data['images']) === false
                && in_array($openGraph['image'], $data['images']) === false;

            if ($addImage)
            {
                array_unshift($data['images'], $openGraph['image']);
            }
        }

        // --------------------------------------

        // reset last url
        self::$lastUrl = null;

        return $data;
    }

    /**
     * @param array $parsedOpenTagElements
     *
     * @return array|null
     */
    private static function aggregateOpenGraph(array $parsedOpenTagElements)
    {
        $data = [];
        $metaData = self::extractParsedOpenTagElementsByAttr($parsedOpenTagElements, 'meta', 'property', 'og:');

        foreach ($metaData as $meta)
        {
            if (empty($meta['content']) === false)
            {
                $data[str_replace('og:', '', strtolower($meta['property']))] = $meta['content'];
            }
        }

        return empty($data) === false ? $data : null;
    }

    /**
     * @param array $parsedOpenTagElements
     *
     * @return array|null
     */
    private static function aggregateTwitter(array $parsedOpenTagElements)
    {
        $data = [];
        $metaData = self::extractParsedOpenTagElementsByAttr($parsedOpenTagElements, 'meta', 'name', 'twitter:');

        foreach ($metaData as $meta)
        {
            $data[str_replace('twitter:', '', strtolower($meta['name']))] = isset($meta['content']) ? $meta['content'] : $meta['value'];
        }

        return empty($data) === false ? $data : null;
    }

    /**
     * @param array $parsedOpenTagElements
     *
     * @return array|null
     */
    private static function aggregateDefaultMetas(array $parsedOpenTagElements)
    {
        $data = [];

        $elements = self::extractParsedOpenTagElementsByAttr($parsedOpenTagElements, 'meta', 'name', '(description|keywords)');

        foreach ($elements as $meta)
        {
            $data[strtolower($meta['name'])] = $meta['content'];
        }

        return empty($data) === false ? $data : null;
    }

    /**
     * @param array $parsedOpenTagElements
     * @param null|string $urlRoot
     *
     * @return array
     */
    private static function aggregateImages(array $parsedOpenTagElements, $urlRoot = null)
    {
        $data = [];

        $elements = [
            'link' => self::extractParsedOpenTagElementsByAttr($parsedOpenTagElements, 'link', 'rel', 'image_src'),
            'img'  => self::extractParsedOpenTagElementsByAttr($parsedOpenTagElements, 'img', 'src'),
        ];

        foreach ($elements as $type => $images)
        {
            foreach ($images as $attrs)
            {
                $url = self::fixRelativeUrls($type === 'link' ? $attrs['href'] : $attrs['src'], $urlRoot);

                if (strpos($url, 'data:image') === false && in_array($url, $data) === false)
                {
                    $data[] = $url;
                }
            }
        }

        return $data;
    }

    /**
     * @param string $html
     * @param array $tags
     *
     * @return array
     */
    private static function parseOpenTag($html, array $tags)
    {
        $data = [];

        foreach ($tags as $tag)
        {
            if ($matchedTags = self::regexMany($html, '/<\s*' . $tag . '(.*?)\/*\s*>/i'))
            {
                foreach ($matchedTags as $match)
                {
                    if ($matchedAttrs = self::regexMany($match[1], '/(\w+)=(\'|")(.*?)(\'|")/i'))
                    {
                        $_ = [];

                        foreach ($matchedAttrs as $index => $attrs)
                        {
                            $_[strtolower($attrs[1])] = $attrs[3];
                        }

                        $data[$tag][] = $_;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param string $html
     * @param array $tags
     *
     * @return array
     */
    private static function parseClosedTags($html, array $tags)
    {
        $data = [];

        foreach ($tags as $tag => $tagLabel)
        {
            $tagLabel = strtolower($tagLabel);

            if ($matchedTags = self::regexMany($html, '/<\s*' . $tag . '\s*>(.*?)<\s*\/\s*' . $tag . '\s*>/ui'))
            {
                foreach ($matchedTags as $matched)
                {
                    if (isset($data[$tagLabel]))
                    {
                        if (is_array($data[$tagLabel]) === false)
                        {
                            $data[$tagLabel] = [$data[$tagLabel]];
                        }

                        $data[$tagLabel][] = $matched[1];
                        continue;
                    }

                    $data[$tagLabel] = $matched[1];
                }
            }
        }

        return $data;
    }

    /**
     * @param array $parsedOpenTagElements
     * @param string $tag
     * @param null|string $attr
     * @param null|string $regexFilter
     *
     * @return array
     */
    private static function extractParsedOpenTagElementsByAttr(array $parsedOpenTagElements, $tag, $attr = null, $regexFilter = null)
    {
        $data = [];

        if (isset($parsedOpenTagElements[$tag]))
        {
            foreach ($parsedOpenTagElements[$tag] as $elm)
            {
                $matchesAttr = $attr !== null && isset($elm[$attr]) && ($regexFilter === null || preg_match('/' . $regexFilter . '/i', $elm[$attr]));

                if ($attr === null || $matchesAttr)
                {
                    $data[] = $elm;
                }
            }
        }

        return $data;
    }

    /**
     * @param string $haystack
     * @param string $regex
     *
     * @return array|null
     */
    private static function regexMany($haystack, $regex)
    {
        if (preg_match_all($regex, $haystack, $matched, PREG_SET_ORDER))
        {
            return $matched;
        }

        return null;
    }

    /**
     * @param string $urlItem
     * @param null|string $urlRoot
     *
     * @return string
     */
    private static function fixRelativeUrls($urlItem, $urlRoot)
    {
        if ($urlRoot !== null)
        {
            $parsedUrlRoot = parse_url($urlRoot);
            $parsedUrlItem = parse_url($urlItem);

            if (empty($parsedUrlItem['host']) === true)
            {
                $urlItem = trim($urlRoot, '/') . '/' . trim($urlItem, '/');
            }
            elseif (empty($parsedUrlItem['scheme']) === true)
            {
                $urlItem = $parsedUrlRoot['scheme'] . ':' . $urlItem;
            }
        }

        return $urlItem;
    }
}