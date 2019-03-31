<?php

namespace OCA\FullTextSearch_Solr\Exceptions;


use Throwable;

/**
 * Class DataExtractionException.
 *
 * This exception is thrown when there is an issue extracting the content out of the document.
 * @package OCA\FullTextSearch_Solr\Exceptions
 */
class DataExtractionException extends \Exception {

    public function __construct(string $documentTitle, int $code = 0, Throwable $previous = null) {

        $message = "Error extracting ".$documentTitle;

        parent::__construct($message, $code, $previous);
    }

}