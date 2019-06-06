<?php
declare(strict_types=1);

namespace OCA\FullTextSearch_Solr\Command;


use Exception;
use OC\Core\Command\Base;
use OCA\FullTextSearch_Solr\AppInfo\Application;
use OCA\FullTextSearch_Solr\Platform\SolrPlatform;
use OCP\ILogger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class Configure
 *
 * @package OCA\FullTextSearch_Solr\Command
 */
class Optimize extends Base {

    const COMMAND_NAME = Application::APP_NAME . ':optimize';

    /** @var SolrPlatform */
    private $platform;

    /**
     * Configure constructor.
     *
     * @param SolrPlatform $platform
     */
    public function __construct(SolrPlatform $platform) {
        parent::__construct();

        $this->platform = $platform;
    }


    /**
     *
     */
    protected function configure() {
        parent::configure();
        $this->setName(self::COMMAND_NAME)
             ->setDescription('Optimize the Solr Instance');
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output) {

        $output->writeln('Starting the optimize process of the database');

        $this->platform->loadPlatform();
        $this->platform->optimize();

        $output->writeln('Ending the optimize process of the database');

    }


}

