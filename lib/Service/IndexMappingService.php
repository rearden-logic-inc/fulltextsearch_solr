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


use Solarium\Client;
use OCA\FullTextSearch_Solr\Exceptions\AccessIsEmptyException;
use OCA\FullTextSearch_Solr\Exceptions\ConfigurationException;
use OCP\FullTextSearch\Model\IIndex;
use OCP\FullTextSearch\Model\IndexDocument;


/**
 * Class IndexMappingService
 *
 * @package OCA\FullTextSearch_Solr\Service
 */
class IndexMappingService {


	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * IndexMappingService constructor.
	 *
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(ConfigService $configService, MiscService $miscService) {
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @param Client $client
	 * @param IndexDocument $document
	 *
	 * @return array
	 * @throws ConfigurationException
	 * @throws AccessIsEmptyException
	 */
	public function indexDocumentNew(Client $client, IndexDocument $document): array {
        echo("Running indexDocumentNew");
	    echo("Setting document to " . $document->getSource());

	    $query = $client->createExtract();
	    $query->addFieldMapping('content', 'text');
	    $query->setUprefix('attr_');
	    $query->setFile($document->getSource());
	    $query->setCommit(true);
	    $query->setOmitHeader(false);

	    $doc = $query->createDocument();
	    $doc->id = $document->getProviderId() . ":" . $document->getId();
        $doc->type = 'standard';
	    $doc->body = $this->generateIndexBody($document);

	    $query->setDocument($doc);

	    $result = $client->extract($query);

	    echo("Result: ". implode("|", $result->getData()));

	    return $result->getData();

//		$index = [
//			'index' =>
//				[
//					'index' => $this->configService->getSolrIndex(),
//					'id'    => $document->getProviderId() . ':' . $document->getId(),
//					'type'  => 'standard',
//					'body'  => $this->generateIndexBody($document)
//				]
//		];
//
//		$this->onIndexingDocument($document, $index);
//
//		return $client->index($index['index']);
	}


	/**
	 * @param Client $client
	 * @param IndexDocument $document
	 *
	 * @return array
	 * @throws ConfigurationException
	 * @throws AccessIsEmptyException
	 */
	// TODO:
	public function indexDocumentUpdate(Client $client, IndexDocument $document): array {

	   echo("Running indexDocumentUpdate");

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
	 * @throws ConfigurationException
	 */
	public function indexDocumentRemove(Client $client, string $providerId, string $documentId) {
		$index = [
			'index' =>
				[
					'index' => $this->configService->getSolrIndex(),
					'id'    => $providerId . ':' . $documentId,
					'type'  => 'standard'
				]
		];

		try {
			$client->delete($index['index']);
		} catch (Missing404Exception $e) {
		}
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
			'owner'    => $access->getOwnerId(),
			'users'    => $access->getUsers(),
			'groups'   => $access->getGroups(),
			'circles'  => $access->getCircles(),
			'links'    => $access->getLinks(),
			'metatags' => $document->getMetaTags(),
			'subtags'  => $document->getSubTags(true),
			'tags'     => $document->getTags(),
			'hash'     => $document->getHash(),
			'provider' => $document->getProviderId(),
			'source'   => $document->getSource(),
			'title'    => $document->getTitle(),
			'parts'    => $document->getParts()
		];
//		}

//		if ($index->isStatus(IIndex::INDEX_CONTENT)) {
		$body['content'] = $document->getContent();

//		}

		return array_merge($document->getInfoAll(), $body);
	}


	/**
	 * @param bool $complete
	 *
	 * @return array
	 * @throws ConfigurationException
	 */
	public function generateGlobalMap(bool $complete = true): array {

		$params = [
			'index' => $this->configService->getSolrIndex()
		];

		if ($complete === false) {
			return $params;
		}

		$params['body'] = [
			'settings' => [
				'analysis' => [
					'filter'      => [
						'shingle' => [
							'type' => 'shingle'
						]
					],
					'char_filter' => [
						'pre_negs'  => [
							'type'        => 'pattern_replace',
							'pattern'     => '(\\w+)\\s+((?i:never|no|nothing|nowhere|noone|none|not|havent|hasnt|hadnt|cant|couldnt|shouldnt|wont|wouldnt|dont|doesnt|didnt|isnt|arent|aint))\\b',
							'replacement' => '~$1 $2'
						],
						'post_negs' => [
							'type'        => 'pattern_replace',
							'pattern'     => '\\b((?i:never|no|nothing|nowhere|noone|none|not|havent|hasnt|hadnt|cant|couldnt|shouldnt|wont|wouldnt|dont|doesnt|didnt|isnt|arent|aint))\\s+(\\w+)',
							'replacement' => '$1 ~$2'
						]
					],
					'analyzer'    => [
						'analyzer' => [
							'type'      => 'custom',
							'tokenizer' => $this->configService->getAppValue(
								ConfigService::ANALYZER_TOKENIZER
							),
							'filter'    => ['lowercase', 'stop', 'kstem']
						]
					]
				]
			],
			'mappings' => [
				'standard' => [
					'dynamic'    => true,
					'properties' => [
						'source'   => [
							'type' => 'keyword'
						],
						'title'    => [
							'type'        => 'text',
							'analyzer'    => 'keyword',
							'term_vector' => 'yes',
							'copy_to'     => 'combined'
						],
						'provider' => [
							'type' => 'keyword'
						],
						'tags'     => [
							'type' => 'keyword'
						],
						'metatags' => [
							'type' => 'keyword'
						],
						'subtags'  => [
							'type' => 'keyword'
						],
						'content'  => [
							'type'        => 'text',
							'analyzer'    => 'analyzer',
							'term_vector' => 'yes',
							'copy_to'     => 'combined'
						],
						'owner'    => [
							'type' => 'keyword'
						],
						'users'    => [
							'type' => 'keyword'
						],
						'groups'   => [
							'type' => 'keyword'
						],
						'circles'  => [
							'type' => 'keyword'
						],
						'links'    => [
							'type' => 'keyword'
						],
						'hash'     => [
							'type' => 'keyword'
						],
						'combined' => [
							'type'        => 'text',
							'analyzer'    => 'analyzer',
							'term_vector' => 'yes'
						]
						//						,
						//						'topics'   => [
						//							'type'  => 'text',
						//							'index' => 'not_analyzed'
						//						],
						//						'places'   => [
						//							'type'  => 'text',
						//							'index' => 'not_analyzed'
						//						]
					]
				]
			]
		];

		return $params;
	}


	/**
	 * @param bool $complete
	 *
	 * @return array
	 */
	public function generateGlobalIngest(bool $complete = true): array {

		$params = ['id' => 'attachment'];

		if ($complete === false) {
			return $params;
		}

		$params['body'] = [
			'description' => 'attachment',
			'processors'  => [
				[
					'attachment' => [
						'field'         => 'content',
						'indexed_chars' => -1
					],
					'convert'    => [
						'field'        => 'attachment.content',
						'type'         => 'string',
						'target_field' => 'content'
					],
					'remove'     => [
						'field'          => 'attachment.content',
						'ignore_failure' => true
					]
				]
			]
		];

		return $params;
	}


	/**
	 * @param string $providerId
	 *
	 * @return array
	 * @throws ConfigurationException
	 */
	public function generateDeleteQuery(string $providerId): array {
		$params = [
			'index' => $this->configService->getSolrIndex(),
			'type'  => 'standard'
		];

		$params['body']['query']['match'] = ['provider' => $providerId];

		return $params;
	}

}

