<?php

namespace OCA\FullTextSearch_Solr\Utilities;


class Utils {

    const DOCUMENT_ID_TIE = '!';
    const USER_PREFIX = 'nc_';

    public static function generateDocumentIdentifier(string $providerId, string $documentId) {
        return $providerId . self::DOCUMENT_ID_TIE . $documentId;
    }

    public static function parseDocumentIdentifier(string $identifier) {
        return explode(self::DOCUMENT_ID_TIE, $identifier, 2);
    }

    public static function createDocumentField(string $identifier) {
        return self::USER_PREFIX.$identifier;
    }

}