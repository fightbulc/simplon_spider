<?php

namespace Simplon\Spider;

/**
 * Class SpiderException
 * @package Simplon\Spider
 */
class SpiderException extends \Exception
{
    const REQUEST_ERROR_CODE = 1000;
    const HTTP_ERROR_CODE = 1100;
}