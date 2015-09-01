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
            $response = Request::get($url);

            if ($response->getHttpCode() === 200)
            {
                return self::parse($response->getBody(), $response->getLastUrl());
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

        $images = self::aggregateImages($parsedOpenTagElements, $url);

        if ($images !== null)
        {
            $data['images'] = $images;
        }

        // --------------------------------------
        // handle facebook open graph

        $openGraph = self::aggregateOpenGraph($parsedOpenTagElements);

        if ($openGraph !== null)
        {
            $data['open-graph'] = $openGraph;

            $addImage =
                empty($openGraph['image']) === false
                && empty($images) === false
                && in_array($openGraph['image'], $images) === false;

            if ($addImage)
            {
                $data['images'][] = $openGraph['image'];
            }
        }

        // --------------------------------------
        // handle twitter

        $twitter = self::aggregateTwitter($parsedOpenTagElements);

        if ($twitter !== null)
        {
            $data['twitter'] = $twitter;

            $addImage =
                empty($twitter['image']) === false
                && empty($images) === false
                && in_array($twitter['image'], $images) === false;

            if ($addImage)
            {
                $data['images'][] = $twitter['image'];
            }
        }

        // --------------------------------------

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
            $data[str_replace('og:', '', $meta['property'])] = $meta['content'];
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
            $data[str_replace('twitter:', '', $meta['name'])] = isset($meta['content']) ? $meta['content'] : $meta['value'];
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
            $data[$meta['name']] = $meta['content'];
        }

        return empty($data) === false ? $data : null;
    }

    /**
     * @param array $parsedOpenTagElements
     * @param null|string $urlRoot
     *
     * @return array|null
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

                if (in_array($url, $data) === false)
                {
                    $data[] = $url;
                }
            }
        }

        return empty($data) === false ? $data : null;
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
                    if ($matchedAttrs = self::regexMany($match[1], '/(\w+)="(.*?)"/i'))
                    {
                        $_ = [];

                        foreach ($matchedAttrs as $index => $attrs)
                        {
                            $_[$attrs[1]] = $attrs[2];
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
            if ($matchedTags = self::regexMany($html, '/<\s*' . $tag . '\s*>(.*?)<\s*\/\s*' . $tag . '\s*>/i'))
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
            if (strpos($urlItem, 'http') === false)
            {
                $urlItem = trim($urlRoot, '/') . '/' . trim($urlItem, '/');
            }
        }

        return $urlItem;
    }
}