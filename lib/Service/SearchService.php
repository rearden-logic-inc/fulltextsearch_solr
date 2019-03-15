<?php
declare(strict_types=1);


/**
 * FullTextSearch_ElasticSearch - Use Elasticsearch to index the content of your nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018
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
use Solarium\Client;
use Exception;
use OCA\FullTextSearch_Solr\Exceptions\ConfigurationException;
use OCA\FullTextSearch_Solr\Exceptions\SearchQueryGenerationException;
use OCP\FullTextSearch\Model\DocumentAccess;
use OCP\FullTextSearch\Model\IndexDocument;
use OCP\FullTextSearch\Model\ISearchResult;


/**
 * Class SearchService
 *
 * @package OCA\FullTextSearch_Solr\Service
 */
class SearchService {


	use TArrayTools;


	/** @var SearchMappingService */
	private $searchMappingService;

	/** @var MiscService */
	private $miscService;


	/**
	 * SearchService constructor.
	 *
	 * @param SearchMappingService $searchMappingService
	 * @param MiscService $miscService
	 */
	public function __construct(
		SearchMappingService $searchMappingService, MiscService $miscService
	) {
		$this->searchMappingService = $searchMappingService;
		$this->miscService = $miscService;
	}

	/**
	 * @param Client $client
	 * @param ISearchResult $searchResult
	 * @param DocumentAccess $access
	 *
	 * @throws Exception
	 */
	// TODO
	public function searchRequest(
		Client $client, ISearchResult $searchResult, DocumentAccess $access
	) {

	    $this->miscService->log("Search result requested.");
	    $this->miscService->log("Request: " . $searchResult->getRequest()->getSearch());
	    $this->miscService->log("Provider: ". $searchResult->getProvider()->getId());

	    $selectQuery = $client->createSelect();

	    $selectQuery->setQuery($searchResult->getRequest()->getSearch());

	    $resultSet = $client->execute($selectQuery);

        $this->miscService->debugToFile("searchResultSet", $resultSet);

        $this->updateSearchResult($searchResult, $access, $resultSet->getResponse()->getBody());

	}


//	/**
//	 * @param Client $client
//	 * @param IFullTextSearchProvider $provider
//	 * @param DocumentAccess $access
//	 * @param SearchResult $result
//	 *
//	 * @return SearchResult
//	 * @throws ConfigurationException
//	 */
//	public function fillSearchResult(
//		Client $client, IFullTextSearchProvider $provider, DocumentAccess $access,
//		SearchResult $searchResult
//	) {
//		try {
//			$query = $this->searchMappingService->generateSearchQuery(
//				$provider, $access, $searchResult->getRequest()
//			);
//		} catch (SearchQueryGenerationException $e) {
//			return null;
//		}
//
//		try {
//			$result = $client->search($query['params']);
//		} catch (Exception $e) {
//			$this->miscService->log(
//				'debug - request: ' . json_encode($searchResult->getRequest()) . '   - query: '
//				. json_encode($query)
//			);
//			throw $e;
//		}
//
//		$this->updateSearchResult($searchResult, $result);
//
//		foreach ($result['hits']['hits'] as $entry) {
//			$searchResult->addDocument($this->parseSearchEntry($entry, $access->getViewerId()));
//		}
//
//		return $searchResult;
//	}


	/**
	 * @param Client $client
	 * @param string $providerId
	 * @param string $documentId
	 *
	 * @return IndexDocument
	 * @throws ConfigurationException
	 */
	//TODO
	public function getDocument(Client $client, string $providerId, string $documentId
	): IndexDocument {
	    return null;
//		$query = $this->searchMappingService->getDocumentQuery($providerId, $documentId);
//		$result = $client->get($query);
//
//		$access = new DocumentAccess($result['_source']['owner']);
//		$access->setUsers($result['_source']['users']);
//		$access->setGroups($result['_source']['groups']);
//		$access->setCircles($result['_source']['circles']);
//		$access->setLinks($result['_source']['links']);
//
//		$index = new IndexDocument($providerId, $documentId);
//		$index->setAccess($access);
//		$index->setMetaTags($result['_source']['metatags']);
//		$index->setSubTags($result['_source']['subtags']);
//		$index->setTags($result['_source']['tags']);
////		$index->setMore($result['_source']['more']);
////		$index->setInfo($result['_source']['info']);
//		$index->setHash($result['_source']['hash']);
//		$index->setSource($result['_source']['source']);
//		$index->setTitle($result['_source']['title']);
//		$index->setParts($result['_source']['parts']);
//
//		$content = $this->get('content', $result['_source'], '');
//		$index->setContent($content);
//
//		return $index;
	}


	/**
	 * @param ISearchResult $searchResult
	 * @param array $result
	 */
	private function updateSearchResult(ISearchResult $searchResult, DocumentAccess $access, string $result) {
		$searchResult->setRawResult($result);

		$result = json_decode($result);

		$this->miscService->debugToFile('result_decoded', $result);

		$searchResult->setTotal($result->response->numFound);
		$searchResult->setMaxScore(intval($result->response->maxScore * 100));  // 100 is arbitrary
		$searchResult->setTime(1);   // Don't have a means of fetching this value yet. value is milliseconds
		$searchResult->setTimedOut(false);

   		foreach ($result->response->docs as $entry) {
			$searchResult->addDocument($this->parseSearchEntry($entry, $access->getViewerId()));
		}
	}


	/**
	 * @param array $entry
	 * @param string $viewerId
	 *
	 * @return IndexDocument
	 */
	private function parseSearchEntry(object $entry, string $viewerId): IndexDocument {
		$access = new DocumentAccess();
		$access->setViewerId($viewerId);

		list($providerId, $documentId) = explode(':', $entry->id, 2);
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

        // TODO: Figure out highlighting
//		$document->setExcerpts(
//			$this->parseSearchEntryExcerpts(
//				(array_key_exists('highlight', $entry)) ? $entry['highlight'] : []
//			)
//		);

		return $document;
	}


	private function parseSearchEntryExcerpts(array $highlight): array {
		$result = [];
		foreach (array_keys($highlight) as $k) {
			$result = array_merge($highlight[$k]);
		}

		return $result;
	}

}

