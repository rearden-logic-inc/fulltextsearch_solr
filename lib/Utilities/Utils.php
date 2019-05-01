<?php

namespace OCA\FullTextSearch_Solr\Utilities;


class Utils {

    public static function generateDocumentIdentifier(string $providerId, string $documentId) {
        return $providerId . "!" . $documentId;
    }

}