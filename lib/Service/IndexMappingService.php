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


use Exception;
use OCA\Files_FullTextSearch\Model\FilesDocument;
use OCA\FullTextSearch\Exceptions\NotIndexableDocumentException;
use OCA\FullTextSearch\Exceptions\ProviderIsNotCompatibleException;
use OCA\FullTextSearch_Solr\Exceptions\AccessIsEmptyException;
use OCA\FullTextSearch_Solr\Exceptions\DataExtractionException;
use OCP\Files\IRootFolder;
use OCP\FullTextSearch\Model\IndexDocument;
use OCP\ILogger;
use Solarium\Client;
use Solarium\Exception\HttpException as SolariumHttpException;


/**
 * Class IndexMappingService
 *
 * @package OCA\FullTextSearch_Solr\Service
 */
class IndexMappingService {


    /** @var ConfigService */
    private $configService;

    /** @var IRootFolder */
    private $rootFolder;

    /** @var ILogger */
    private $logger;

    /**
     * IndexMappingService constructor.
     *
     * @param ConfigService $configService
     * @param IRootFolder $rootFolder
     * @param ILogger $logger
     */
    public function __construct(ConfigService $configService, IRootFolder $rootFolder, ILogger $logger) {
        $this->configService = $configService;
        $this->rootFolder = $rootFolder;
        $this->logger = $logger;
    }


    /**
     * @param Client $client
     * @param IndexDocument $document
     *
     * @return array
     * @throws DataExtractionException Thrown when solr throws an exception
     * @throws NotIndexableDocumentException Thrown when file type is not supported
     * @throws ProviderIsNotCompatibleException thrown when provider is not compatible with this platform
     */
    public function indexDocumentNew(Client $client, IndexDocument $document): array {

        if ($document->getProviderId() == 'files') {

            /** @var FilesDocument $document*/
            if ($document->getType() == 'dir') {
                throw new NotIndexableDocumentException("File is a directory");
            }

            return $this->indexFile($client, $document);

        }

        throw new ProviderIsNotCompatibleException("Solr Platform does not support provider type: ".$document->getProviderId());

    }

    /**
     * Create an extract query for SOLR and provide the file contents.
     * @param Client $client
     * @param IndexDocument $document
     * @return array
     * @throws DataExtractionException
     */
    private function indexFile(Client $client, IndexDocument $document): array {
        $this->logger->debug("Running indexDocumentNew");
        $this->logger->debug("Creating temporary file.");

        // Currently have to write out the content of the file to a temporary location because only
        // the content is provided.
        $tmpfname = tempnam(sys_get_temp_dir(), $document->getTitle());
        $handle = fopen($tmpfname, "w");
        fwrite($handle, base64_decode($document->getContent()));
        fclose($handle);

        // Create the extract query for the file
        $query = $client->createExtract();
        $query->setFile($tmpfname);
        $query->setCommit(true);  // Tell the servlet to commit the new data immediately

        // Generate any additional metadata files to be associated with the document.
        $doc = $query->createDocument();
        $doc->id = $this->generateDocumentIdentifier($document->getProviderId(), $document->getId());
        $query->setDocument($doc);

        // Execute the query
        try {
            $result = $client->extract($query);
            $this->logger->debug("Result", array('result' => $result));
            return $result->getData();
        } catch (SolariumHttpException $e) {
            throw new DataExtractionException($document->getTitle(), $e->getCode(), $e);
        }

    }

    private function generateDocumentIdentifier(string $providerId, string $documentId) {
        return $providerId . ":" . $documentId;
    }

    /**
     * @param Client $client
     * @param IndexDocument $document
     *
     * @return array
     */
    public function indexDocumentUpdate(Client $client, IndexDocument $document): array {

        $this->logger->debug("Running indexDocumentUpdate");

        return null;
//		$index = [
//			'index' =>
//				[
//					'index' => $this->configService->getSolrIndex(),
//					'id'    => $document->getProviderId() . ':' . $document->getId(),
//					'type'  => 'standard',
//					'body'  => ['doc' => $this->generateIndexBody($document)]
//				]
//		];
//
//		$this->onIndexingDocument($document, $index);
//		try {
//			return $client->update($index['index']);
//		} catch (Missing404Exception $e) {
//			return $this->indexDocumentNew($client, $document);
//		}
    }

    /**
     * @param Client $client
     * @param string $providerId
     * @param string $documentId
     *
     */
    public function indexDocumentRemove(Client $client, string $providerId, string $documentId) {

        $this->logger->debug("Removing document:", array('providerId' => $providerId,
            'documentId' => $documentId));

        // get an update query instance
        $update = $client->createUpdate();

        // add the delete id and a commit command to the update query
        $update->addDeleteById($this->generateDocumentIdentifier($providerId, $documentId));
        $update->addCommit();

        // this executes the query and returns the result
        $client->update($update);

    }

    /**
     * @param IndexDocument $document
     * @param array $arr
     */
    public function onIndexingDocument(IndexDocument $document, array &$arr) {
        if ($document->getContent() !== ''
            && $document->isContentEncoded() === IndexDocument::ENCODED_BASE64) {
            $arr['index']['pipeline'] = 'attachment';
        }
    }


    /**
     * @param IndexDocument $document
     *
     * @return array
     * @throws AccessIsEmptyException
     */
    public function generateIndexBody(IndexDocument $document): array {

        $access = $document->getAccess();
        if ($access === null) {
            throw new AccessIsEmptyException('DocumentAccess is Empty');
        }

        // TODO: check if we can just update META or just update CONTENT.
//		$index = $document->getIndex();
//		$body = [];
//		if ($index->isStatus(IIndex::INDEX_META)) {
        $body = [
            'owner' => $access->getOwnerId(),
            'users' => $access->getUsers(),
            'groups' => $access->getGroups(),
            'circles' => $access->getCircles(),
            'links' => $access->getLinks(),
            'metatags' => $document->getMetaTags(),
            'subtags' => $document->getSubTags(true),
            'tags' => $document->getTags(),
            'hash' => $document->getHash(),
            'provider' => $document->getProviderId(),
            'source' => $document->getSource(),
            'title' => $document->getTitle(),
            'parts' => $document->getParts()
        ];
//		}

        return array_merge($document->getInfoAll(), $body);
    }

}

