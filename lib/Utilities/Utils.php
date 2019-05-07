<?php

namespace OCA\FullTextSearch_Solr\Utilities;


class Utils {

    const DOCUMENT_ID_TIE = '!';

    public static function generateDocumentIdentifier(string $providerId, string $documentId) {
        return $providerId . self::DOCUMENT_ID_TIE . $documentId;
    }

    public static function parseDocumentIdentifier(string $identifier) {
        return explode(self::DOCUMENT_ID_TIE, $identifier, 2);
    }

}