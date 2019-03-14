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


namespace OCA\FullTextSearch_Solr\Platform;


use daita\MySmallPhpTools\Traits\TPathTools;
use Solarium\Client;
use Exception;
use OCA\FullTextSearch_Solr\Exceptions\AccessIsEmptyException;
use OCA\FullTextSearch_Solr\Exceptions\ConfigurationException;
use OCA\FullTextSearch_Solr\Service\ConfigService;
use OCA\FullTextSearch_Solr\Service\IndexService;
use OCA\FullTextSearch_Solr\Service\MiscService;
use OCA\FullTextSearch_Solr\Service\SearchService;
use OCP\FullTextSearch\IFullTextSearchPlatform;
use OCP\FullTextSearch\Model\DocumentAccess;
use OCP\FullTextSearch\Model\IIndex;
use OCP\FullTextSearch\Model\IndexDocument;
use OCP\FullTextSearch\Model\IRunner;
use OCP\FullTextSearch\Model\ISearchResult;

use OCA\FullTextSearch_Solr\Solr\Http;


/**
 * Class SolrPlatform
 *
 * This is the implementation of the FullTextSearchPlatform interface that is expected by the FullTextSearch
 * application.
 *
 * @package OCA\FullTextSearch_ElasticSearch\Platform
 */
class SolrPlatform implements IFullTextSearchPlatform {


	use TPathTools;


	/** @var ConfigService */
	private $configService;

	/** @var IndexService */
	private $indexService;

	/** @var SearchService */
	private $searchService;

	/** @var MiscService */
	private $miscService;

	/** @var Client */
	private $client;

	/** @var IRunner */
	private $runner;


	/**
	 * ElasticSearchPlatform constructor.
	 *
	 * @param ConfigService $configService
	 * @param IndexService $indexService
	 * @param SearchService $searchService
	 * @param MiscService $miscService
	 */
	public function __construct(
		ConfigService $configService, IndexService $indexService, SearchService $searchService,
		MiscService $miscService
	) {
		$this->configService = $configService;
		$this->indexService = $indexService;
		$this->searchService = $searchService;
		$this->miscService = $miscService;
	}


	/**
	 * return a unique Id of the platform.
	 */
	public function getId(): string {
		return 'solr';
	}


	/**
	 * return a unique Id of the platform.
	 */
	public function getName(): string {
		return 'Solr';
	}


	/**
	 * @return array
	 * @throws ConfigurationException
	 */
	public function getConfiguration(): array {

		$result = [];
		$hosts = $this->configService->getSolrHost();

		foreach ($hosts as $host) {
			$parsedHost = parse_url($host);
			$safeHost = $parsedHost['scheme'] . '://';
			if (array_key_exists('user', $parsedHost)) {
				$safeHost .= $parsedHost['user'] . ':' . '********' . '@';
			}
			$safeHost .= $parsedHost['host'];
			$safeHost .= ':' . $parsedHost['port'];

			$result[] = $safeHost;
		}

		return [
			'solr_host'  => $result,
			'solr_index' => $this->configService->getSolrIndex()
		];
	}


	/**
	 * @param IRunner $runner
	 */
	public function setRunner(IRunner $runner) {
		$this->runner = $runner;
	}


	/**
	 * Called when loading the platform.
	 *
	 * Loading some container and connect to ElasticSearch.
	 *
	 * @throws ConfigurationException
	 * @throws Exception
	 */
	// TODO
	public function loadPlatform() {
	    $this->miscService->log("Loading solarium platform", 2);
		try {
			$this->connect($this->configService->getSolrHost());
		} catch (ConfigurationException $e) {
			throw $e;
		}
	}


	/**
	 * not used yet.
	 *
	 * @return bool
	 */
	public function testPlatform(): bool {
//        $this->miscService->log("Executing Ping");
//	    $ping = $this->client->createPing();

	    try {
//            $this->client->ping($ping);
//            $this->miscService->log('Ping Successful');
            return true;
        } catch (Exception $e) {
	        $this->miscService->log('Ping Failed');
	        $this->miscService->log($e->getTraceAsString());
        }
        return true;
	}


	/**
	 * called before any index
	 *
	 * We create a general index.
	 *
	 * @throws ConfigurationException
	 * @throws BadRequest400Exception
	 */
	// TODO
	public function initializeIndex() {
//		$this->indexService->initializeIndex($this->client);
        $this->miscService->log("Initializing Index");
	}


	/**
	 * resetIndex();
	 *
	 * Called when admin wants to remove an index specific to a $provider.
	 * $provider can be null, meaning a reset of the whole index.
	 *
	 * @param string $providerId
	 *
	 * @throws ConfigurationException
	 */
	// TODO
	public function resetIndex(string $providerId) {
        $this->miscService->log("Reset Index");
//		if ($providerId === 'all') {
//			$this->indexService->resetIndexAll($this->client);
//		} else {
//			$this->indexService->resetIndex($this->client, $providerId);
//		}
	}


	/**
	 * @param IndexDocument $document
	 *
	 * @return IIndex
	 */
	// TODO
	public function indexDocument(IndexDocument $document): IIndex {

	    echo("Asked to index document: ".$document->getId()."\n");
	    echo("Source: ".$document->getSource()."\n");

		$document->initHash();

		try {
			$result = $this->indexService->indexDocument($this->client, $document);

			$index = $this->indexService->parseIndexResult($document->getIndex(), $result);

			$this->updateNewIndexResult(
				$document->getIndex(), json_encode($result), 'ok',
				IRunner::RESULT_TYPE_SUCCESS
			);

			return $index;
		} catch (Exception $e) {
			$this->updateNewIndexResult(
				$document->getIndex(), '', 'issue while indexing, testing with empty content',
				IRunner::RESULT_TYPE_WARNING
			);

			$this->manageIndexErrorException($document, $e);
		}

//		try {
//			$result = $this->indexDocumentError($document, $e);
//			$index = $this->indexService->parseIndexResult($document->getIndex(), $result);
//
//			$this->updateNewIndexResult(
//				$document->getIndex(), json_encode($result), 'ok',
//				IRunner::RESULT_TYPE_WARNING
//			);
//
//			return $index;
//		} catch (Exception $e) {
//			$this->updateNewIndexResult(
//				$document->getIndex(), '', 'fail',
//				IRunner::RESULT_TYPE_FAIL
//			);
//			$this->manageIndexErrorException($document, $e);
//		}

		return $document->getIndex();
	}


	/**
	 * @param IndexDocument $document
	 * @param Exception $e
	 *
	 * @return array
	 * @throws AccessIsEmptyException
	 * @throws ConfigurationException
	 * @throws \Exception
	 */
	// TODO
	private function indexDocumentError(IndexDocument $document, Exception $e): array {

		$this->updateRunnerAction('indexDocumentWithoutContent', true);

		$document->setContent('');
//		$index = $document->getIndex();
//		$index->unsetStatus(Index::INDEX_CONTENT);

		$result = $this->indexService->indexDocument($this->client, $document);

		return $result;
	}


	/**
	 * @param IndexDocument $document
	 * @param Exception $e
	 */
	//TODO
	private function manageIndexErrorException(IndexDocument $document, Exception $e) {

		$message = $this->parseIndexErrorException($e);
		$document->getIndex()
				 ->addError($message, get_class($e), IIndex::ERROR_SEV_3);
		$this->updateNewIndexError(
			$document->getIndex(), $message, get_class($e), IIndex::ERROR_SEV_3
		);
	}


	/**
	 * @param Exception $e
	 *
	 * @return string
	 */
	private function parseIndexErrorException(Exception $e): string {

		$arr = json_decode($e->getMessage(), true);
		if (!is_array($arr)) {
			return $e->getMessage();
		}

		if (array_key_exists('reason', $arr['error']['root_cause'][0])) {
			return $arr['error']['root_cause'][0]['reason'];
		}

		return $e->getMessage();
	}


	/**
	 * {@inheritdoc}
	 * @throws ConfigurationException
	 */
	public function deleteIndexes(array $indexes) {
	    $this->miscService->log("Asked to delete indices");
//		try {
//			$this->indexService->deleteIndexes($this->client, $indexes);
//		} catch (ConfigurationException $e) {
//			throw $e;
//		}
	}


	/**
	 * {@inheritdoc}
	 * @throws Exception
	 */
	public function searchRequest(ISearchResult $result, DocumentAccess $access) {
	    $this->miscService->log("Search Request");
//		$this->searchService->searchRequest($this->client, $result, $access);
	}


	/**
	 * @param string $providerId
	 * @param string $documentId
	 *
	 * @return IndexDocument
	 * @throws ConfigurationException
	 */
	public function getDocument(string $providerId, string $documentId): IndexDocument {
	    $this->miscService->log("Asked to retrieve document");
//		return $this->searchService->getDocument($this->client, $providerId, $documentId);
        return null;
	}


	private function cleanHost($host) {
		return $this->withoutEndSlash($host, false, false);
	}

	/**
	 * @param array $hosts
	 *
	 * @throws Exception
	 */
	private function connect(array $hosts) {

		try {
		    // TODO: Configure this correctly to use the values that are stored in the configuration details
			$config = array(
                'endpoint' => array(
                    'solr' => array(
                        'host' => 'solr',
                        'port' => 8983,
                        'path' => '/solr/',
                        'core' => 'mycore',  // This is probably the index value
                    )
                )
            );
			$this->client = new Client($config);
//			$this->client->setAdapter('Solarium\Core\Client\Adapter\PeclHttp');
		} catch (Exception $e) {
			throw $e;
		}
	}


	/**
	 * @param string $action
	 * @param bool $force
	 *
	 * @throws Exception
	 */
	private function updateRunnerAction(string $action, bool $force = false) {
		if ($this->runner === null) {
			return;
		}

		$this->runner->updateAction($action, $force);
	}


	/**
	 * @param IIndex $index
	 * @param string $message
	 * @param string $exception
	 * @param int $sev
	 */
	private function updateNewIndexError(IIndex $index, string $message, string $exception, int $sev
	) {
		if ($this->runner === null) {
			return;
		}

		$this->runner->newIndexError($index, $message, $exception, $sev);
	}


	/**
	 * @param IIndex $index
	 * @param string $message
	 * @param string $status
	 * @param int $type
	 */
	private function updateNewIndexResult(IIndex $index, string $message, string $status, int $type
	) {
		if ($this->runner === null) {
			return;
		}

		$this->runner->newIndexResult($index, $message, $status, $type);
	}


}
