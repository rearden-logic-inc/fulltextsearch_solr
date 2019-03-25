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


namespace OCA\FullTextSearch_Solr\Platform;


use daita\MySmallPhpTools\Traits\TPathTools;
use OCP\ILogger;
use Solarium\Client;
use Exception;
use OCA\FullTextSearch_Solr\Exceptions\ConfigurationException;
use OCA\FullTextSearch_Solr\Service\ConfigService;
use OCA\FullTextSearch_Solr\Service\IndexService;
use OCA\FullTextSearch_Solr\Service\SearchService;
use OCP\FullTextSearch\IFullTextSearchPlatform;
use OCP\FullTextSearch\Model\DocumentAccess;
use OCP\FullTextSearch\Model\IIndex;
use OCP\FullTextSearch\Model\IndexDocument;
use OCP\FullTextSearch\Model\IRunner;
use OCP\FullTextSearch\Model\ISearchResult;


/**
 * Class SolrPlatform
 *
 * This is the implementation of the FullTextSearchPlatform interface that is expected by the FullTextSearch
 * application.
 *
 * @package OCA\FullTextSearch_Solr\Platform
 */
class SolrPlatform implements IFullTextSearchPlatform {


    use TPathTools;


    /** @var ConfigService */
    private $configService;

    /** @var IndexService */
    private $indexService;

    /** @var SearchService */
    private $searchService;

    /** @var Client */
    private $client;

    /** @var IRunner */
    private $runner;

    /** @var ILogger */
    private $logger;


    /**
     * Solr Platform constructor.
     *
     * @param ConfigService $configService
     * @param IndexService $indexService
     * @param SearchService $searchService
     * @param ILogger $logger
     */
    public function __construct(ConfigService $configService, IndexService $indexService, SearchService $searchService,
                                ILogger $logger) {
        $this->configService = $configService;
        $this->indexService = $indexService;
        $this->searchService = $searchService;
        $this->logger = $logger;
    }


    /**
     * Return a unique Id of the platform.
     */
    public function getId(): string {
        return 'solr';
    }


    /**
     * Return a unique Id of the platform.
     */
    public function getName(): string {
        return 'Apache Solr';
    }


    /**
     * @return array
     * @throws ConfigurationException
     */
    public function getConfiguration(): array {

        $result = [];
        $host = $this->configService->getSolrServlet();

        $parsedHost = parse_url($host);
        $safeHost = $parsedHost['scheme'] . '://';
        if (array_key_exists('user', $parsedHost)) {
            $safeHost .= $parsedHost['user'] . ':' . '********' . '@';
        }
        $safeHost .= $parsedHost['host'];
        $safeHost .= ':' . $parsedHost['port'];

        $result[] = $safeHost;

        return [
            'solr_servlet' => $result,
            'solr_core' => $this->configService->getSolrCore()
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
     * Loading some container and connect to Solr.
     *
     * @throws Exception
     */
    public function loadPlatform() {
        $this->logger->debug("Loading solarium platform");
        $this->connect();
    }

    /**
     *
     * @throws Exception
     */
    private function connect() {

        try {
            $url_components = parse_url($this->configService->getSolrServlet());

            $port = 8983;
            if (array_key_exists('port', $url_components)) {
                $port = $url_components['port'];
            }

            $config = array(
                'endpoint' => array(
                    'solr' => array(
                        'host' => $url_components['host'],
                        'port' => $port,
                        'path' => $url_components['path'],
                        'core' => $this->configService->getSolrCore(),
                    )
                )
            );
            $this->client = new Client($config);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * not used yet.
     *
     * @return bool
     */
    public function testPlatform(): bool {
        $this->logger->debug("Executing Ping");
        $ping = $this->client->createPing();

        try {
            $this->client->ping($ping);
            $this->logger->debug('Ping Successful');
            return true;
        } catch (Exception $e) {
            $this->logger->error('Ping Failed');
            $this->logger->logException($e);
        }
        return false;
    }

    /**
     * called before any index
     *
     * We create a general index.
     */
    public function initializeIndex() {
        $this->logger->debug("Initializing Index");
    }

    /**
     * resetIndex();
     *
     * Called when admin wants to remove an index specific to a $provider.
     * $provider can be null, meaning a reset of the whole index.
     *
     * @param string $providerId
     */
    public function resetIndex(string $providerId) {
        $this->logger->debug("Reset Index on provider: " . $providerId);
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
    public function indexDocument(IndexDocument $document): IIndex {

        $this->logger->debug("Asked to index document: ", array($document->getId(), $document->getSource()));

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

        $this->updateNewIndexResult(
            $document->getIndex(), '', 'fail',
            IRunner::RESULT_TYPE_FAIL
        );

        return $document->getIndex();
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

    /**
     * @param IndexDocument $document
     * @param Exception $e
     */
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
     * {@inheritdoc}
     * @throws ConfigurationException
     */
    public function deleteIndexes(array $indexes) {
        try {
            $this->indexService->deleteIndexes($this->client, $indexes);
        } catch (ConfigurationException $e) {
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function searchRequest(ISearchResult $result, DocumentAccess $access) {
        $this->logger->debug("Search Request");
        $this->searchService->searchRequest($this->client, $result, $access);
    }

    /**
     * @param string $providerId
     * @param string $documentId
     *
     * @return IndexDocument
     * @throws ConfigurationException
     */
    public function getDocument(string $providerId, string $documentId): IndexDocument {
        $this->logger->debug("Asked to retrieve document");
//		return $this->searchService->getDocument($this->client, $providerId, $documentId);
        return null;
    }


}
