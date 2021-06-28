<?php

namespace Ahoj;

class TotalPriceExceedsLimitsException extends \Exception
{
}

class InvalidArgumentException extends \InvalidArgumentException
{
}

class ApiErrorException extends \Exception
{
    function __construct($body, $code, $previous = null)
    {
        $message = "Pocas volania API nastal problem. HTTP code: \"$code\", message: ";
        $errMessage = $body;
        if (is_string($body)) {
            $message .= $body;
        }
        if (is_array($body) && array_key_exists('message', $body)) {
            $message .= $body['message'];
        }
        $message .= "\"$body\"";
        parent::__construct();
    }
}

class ContractNotExistException extends \Exception
{
}

class ProductNotAvailableException extends \Exception
{
}
