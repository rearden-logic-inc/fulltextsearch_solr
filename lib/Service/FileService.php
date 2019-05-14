<?php

namespace OCA\FullTextSearch_Solr\Service;

use OCP\Files\File;
use OCA\Files_FullTextSearch\Model\FilesDocument;
use OCA\Files_FullTextSearch\Service\ExtensionService;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class FileService
 *
 * Service is responsible for finding the absolute path to the file that is to be indexed by the system, and adding that
 * information into the index document so that the file doesn't need to be written out to tmp in order to upload.
 * @package OCA\FullTextSearch_Solr\Service
 */
class FileService {

    const PATH_INFO_KEY = 'SOLR_FILE_PATH';

    /** @var EventDispatcher */
    private $eventDispatcher;

    /**
     * FileService constructor.
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(EventDispatcher $eventDispatcher) {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function registerEventHooks() {
        $this->eventDispatcher->addListener(ExtensionService::FILE_INDEXING_EVENT, array($this, 'onFileIndexing'));
    }

    /**
     * @param GenericEvent $event
     */
    public function onFileIndexing($event) {
        /** @var File $file */
        $file = $event->getArgument('file');

        /** @var FilesDocument $document */
        $document = $event->getArgument('document');

        $mountPointPath = $file->getMountPoint()->getStorage()->getLocalFile('');
        $mountPointPath = rtrim($mountPointPath, '/\\');
        $result = $mountPointPath . DIRECTORY_SEPARATOR . $file->getInternalPath();
        $document->setInfo(self::PATH_INFO_KEY, $result);
    }

}
