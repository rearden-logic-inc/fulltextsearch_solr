<?php
declare(strict_types=1);


/**
 * FullTextSearch_Solr - Use Solr to index the content of your nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Robert Robinson <rerobins@gmail.com>
 * @copyright 2019
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\FullTextSearch_Solr\Service;


use daita\MySmallPhpTools\Traits\TArrayTools;
use Exception;
use OCA\FullTextSearch\Model\SearchRequest;
use OCA\FullTextSearch_Solr\Utilities\Utils;
use OCP\FullTextSearch\Model\DocumentAccess;
use OCP\FullTextSearch\Model\IndexDocument;
use OCP\FullTextSearch\Model\ISearchResult;
use OCP\ILogger;
use Solarium\Client;


/**
 * Class SearchService
 *
 * @package OCA\FullTextSearch_Solr\Service
 */
class SearchService {


    use TArrayTools;


    /** @var SearchMappingService */
    private $searchMappingService;

    /** @var ILogger */
    private $logger;


    /**
     * SearchService constructor.
     *
     * @param SearchMappingService $searchMappingService
     * @param ILogger $logger
     */
    public function __construct(SearchMappingService $searchMappingService, ILogger $logger) {
        $this->searchMappingService = $searchMappingService;
        $this->logger = $logger;
    }

    /**
     * @param Client $client
     * @param ISearchResult $searchResult
     * @param DocumentAccess $access
     *
     * @throws Exception
     */

    public function searchRequest(Client $client, ISearchResult $searchResult, DocumentAccess $access) {

        /** @var SearchRequest $request */
        $request = $searchResult->getRequest();
        $this->logger->debug("Search result requested.",
                             array("request" => $request->getSearch(),
                                   "provider" => $searchResult->getProvider()->getId()));

        $selectQuery = $client->createSelect();
        $selectQuery->setFields(['id', Utils::createDocumentField('*'), 'title', 'score']);
        $selectQuery->setOmitHeader(false);
        $selectQuery->setQueryDefaultField(IndexMappingService::TEXT_STORAGE_FIELD);

        $selectQuery->setQuery($request->getSearch());
        $selectQuery->setStart(($request->getPage() -1) * $request->getSize());
        $selectQuery->setRows($request->getSize());

        $hl = $selectQuery->getHighlighting();
        $hl->addField(IndexMappingService::TEXT_STORAGE_FIELD);
        $hl->setSimplePrefix('');
        $hl->setSimplePostfix('');

        // Add the list of tags from the select query to the search request
        if (!empty($request->getTags())) {
            $selectQuery->createFilterQuery('tagFilter')->setQuery(Utils::createDocumentField('tags').':'.implode(' ', $request->getTags()));
        }

        // Add all of the metadata queries to the search request
        if (!empty($request->getSubTags())) {
            foreach ($request->getSubTags() as $key => $value) {
                $selectQuery->createFilterQuery($key)->setQuery(Utils::createDocumentField($key).":\"{$value[0]}\"");
            }
        }

        $resultSet = $client->execute($selectQuery);

        $this->logger->debug("searchResultSet", array('resultSet' => $resultSet));

        $this->updateSearchResult($searchResult, $access, $resultSet->getResponse()->getBody());

    }

    /**
     * @param ISearchResult $searchResult
     * @param DocumentAccess $access
     * @param string $result
     */
    private function updateSearchResult(ISearchResult $searchResult, DocumentAccess $access, string $result) {
        $searchResult->setRawResult($result);

        $result = json_decode($result);

        $this->logger->debug('result_decoded', array("result" => $result));

        $searchResult->setTotal($result->response->numFound);
        $searchResult->setMaxScore(intval($result->response->maxScore * 100));  // 100 is arbitrary
        $searchResult->setTime($result->responseHeader->QTime);
        $searchResult->setTimedOut(false);

        foreach ($result->response->docs as $entry) {
            $entryId = $entry->id;

            $highlighting = $result->highlighting->$entryId;

            $searchResult->addDocument($this->parseSearchEntry($entry, $access->getViewerId(), $highlighting));
        }
    }

    /**
     * @param object $entry
     * @param string $viewerId
     *
     * @return IndexDocument
     */
    private function parseSearchEntry(object $entry, string $viewerId, object $highlighting): IndexDocument {
        $access = new DocumentAccess();
        $access->setViewerId($viewerId);

        list($providerId, $documentId) = Utils::parseDocumentIdentifier($entry->id);
        $document = new IndexDocument($providerId, $documentId);
        $document->setAccess($access);
        $document->setHash('Unknown');  // TODO: Save off the hash some where.
        $document->setScore(strval(intval($entry->score * 100)));
        $document->setSource('Unknown');  // TODO: Save off the source when storing document
        if ($entry->title != null) {
            $document->setTitle($entry->title[0]);
        } else {
            $document->setTitle('Unknown Document Title');
        }

        if (property_exists($highlighting, IndexMappingService::TEXT_STORAGE_FIELD)) {
            $document->setExcerpts(
			    $highlighting->{IndexMappingService::TEXT_STORAGE_FIELD}
		    );
        }

        return $document;
    }


    public function getDocument(Client $client, string $providerId, string $documentId
    ): IndexDocument {
        return null;
    }

}

