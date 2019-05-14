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
use OCA\FullTextSearch\Exceptions\NotIndexableDocumentException;
use OCA\FullTextSearch\Exceptions\ProviderIsNotCompatibleException;
use OCA\FullTextSearch_Solr\Exceptions\DataExtractionException;
use OCA\FullTextSearch_Solr\Utilities\Utils;
use OCP\FullTextSearch\Model\IIndex;
use OCP\FullTextSearch\Model\IndexDocument;
use OCP\ILogger;
use Solarium\Client;


/**
 * Class IndexService
 *
 * @package OCA\FullTextSearch_Solr\Service
 */
class IndexService {


    use TArrayTools;


    /** @var IndexMappingService */
    private $indexMappingService;

    /** @var ILogger */
    private $logger;

    /**
     * IndexService constructor.
     *
     * @param IndexMappingService $indexMappingService
     * @param ILogger $logger
     */
    public function __construct(IndexMappingService $indexMappingService, ILogger $logger) {
        $this->indexMappingService = $indexMappingService;
        $this->logger = $logger;
    }


    /**
     * @param Client $client
     *
     * @return bool
     */
    // TODO
    public function testIndex(Client $client): bool {

//		$map = $this->indexMappingService->generateGlobalMap(false);
//		$map['client'] = [
//			'verbose' => true
//		];
//
//		return $client->indices()
//					  ->exists($map);
        return false;
    }


    /**
     * @param Client $client
     *
     */
    public function initializeIndex(Client $client) {
    }


    /**
     * @param Client $client
     * @param string $providerId
     *
     */
    public function resetIndex(Client $client, string $providerId) {
        $update = $client->createUpdate();
        $update->addDeleteQuery('id:' . Utils::generateDocumentIdentifier($providerId, '*'));
        $update->addCommit();

        $client->update($update);
    }


    /**
     * @param Client $client
     *
     */
    public function resetIndexAll(Client $client) {
        $update = $client->createUpdate();
        $update->addDeleteQuery("id:*");
        $update->addCommit();

        $client->update($update);
    }


    /**
     * @param Client $client
     * @param IIndex[] $indexes
     *
     */
    public function deleteIndexes(Client $client, array $indexes) {
        foreach ($indexes as $index) {
            $this->indexMappingService->indexDocumentRemove(
                $client, $index->getProviderId(), $index->getDocumentId()
            );
        }
    }

    /**
     * @param Client $client
     * @param IndexDocument $document
     *
     * @return array
     * @throws NotIndexableDocumentException
     * @throws ProviderIsNotCompatibleException
     * @throws DataExtractionException
     */
    public function indexDocument(Client $client, IndexDocument $document): array {
        $result = [];
        $index = $document->getIndex();

        if ($index->isStatus(IIndex::INDEX_REMOVE)) {
            $this->indexMappingService->indexDocumentRemove($client, $document->getProviderId(), $document->getId());
        } else {
            // Per documentation of solarium, there is no means of updating a single field of the document, the entire
            // document (including context) must be sent to Solr
            $result = $this->indexMappingService->indexDocumentNew($client, $document);
        }

        return $result;
    }


    /**
     * @param IIndex $index
     * @param array $result
     *
     * @return IIndex
     */
    public function parseIndexResult(IIndex $index, array $result): IIndex {

        $index->setLastIndex();

        $this->logger->debug("Running parse Index Result", array("result" => $result));

//		if (array_key_exists('exception', $result)) {
//			$index->setStatus(IIndex::INDEX_FAILED);
//			$index->addError(
//				$this->get('message', $result, $result['exception']),
//				'',
//				IIndex::ERROR_SEV_3
//			);
//
//			return $index;
//		}
//
//		// TODO: parse result
//		if ($index->getErrorCount() === 0) {
//			$index->setStatus(IIndex::INDEX_DONE);
//		}
        // Hard code the status to done for now.
        $index->setStatus(IIndex::INDEX_DONE);

        return $index;
    }

}
