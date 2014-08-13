<?php

namespace Heyday\SphinxSearch;

use Sphinx\SphinxClient;

class Sphinx
{
    use DbValidateTrait;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var \Heyday\SphinxSearch\SphinxIndexConfiguration[]
     */
    protected $indexes = [];

    /**
     * @var array|\ArrayObject
     */
    protected $defaultDbConfig;

    /**
     * @var string
     */
    protected $binaryLocation = '';

    /**
     * @var string
     */
    protected $configFolder = '../silverstripe-sphinxsearch/configs';

    /**
     * @var string
     */
    protected $indexFolder = '../silverstripe-sphinxsearch/indexes';

    /**
     * @var string
     */
    protected $logFolder = '../silverstripe-sphinxsearch/logs';

    /**
     * @var string
     */
    protected $indexerMemLimit = '128M';

    /**
     * @param \Heyday\SphinxSearch\SphinxIndexConfiguration[] $indexes
     * @param string $host
     * @param int $port
     * @param array|\ArrayObject $defaultDbConfig
     * @param string $binaryLocation
     * @param string $configFolder
     * @param string $logFolder
     * @param string $indexFolder
     */
    public function __construct(
        array $indexes,
        $host,
        $port,
        $defaultDbConfig,
        $binaryLocation = null,
        $configFolder = null,
        $logFolder = null,
        $indexFolder = null
    ) {
        $this->setIndexes($indexes);
        $this->setHost($host);
        $this->setPort($port);
        $this->setDefaultDbConfig($defaultDbConfig);

        if (!is_null($binaryLocation)) {
            $this->binaryLocation = $binaryLocation;
        }
        if (!is_null($configFolder)) {
            $this->configFolder = $configFolder;
        }
        if (!is_null($logFolder)) {
            $this->logFolder = $logFolder;
        }
        if (!is_null($indexFolder)) {
            $this->indexFolder = $indexFolder;
        }
    }

    /**
     * @param array|mixed $indexes
     */
    protected function assertValidIndexes($indexes)
    {
        if (!is_array($indexes)) {
            throw new \RuntimeException("Indexes must be an array");
        }

        array_map(function ($index) {
            if (!$index instanceof SphinxIndexConfiguration) {
                throw new \RuntimeException(sprintf(
                    "Allow indexes must be instances of SphinxIndexConfiguration, '%s' given",
                    gettype($index)
                ));
            }
        }, $indexes);
    }

    /**
     * @param string|mixed $host
     * @throws \RuntimeException
     */
    protected function assertValidHost($host)
    {
        if (!is_string($host)) {
            throw new \RuntimeException(sprintf(
                "Host must be a string, '%s' given",
                gettype($host)
            ));
        }
    }

    /**
     * @param int|mixed $port
     * @throws \RuntimeException
     */
    protected function assertValidPort($port)
    {
        if (!is_int($port)) {
            throw new \RuntimeException(sprintf(
                "Port must be a int, '%s' given",
                gettype($port)
            ));
        }
    }

    /**
     * @param \Heyday\SphinxSearch\SphinxIndexConfiguration[] $indexes
     */
    public function setIndexes(array $indexes)
    {
        $this->assertValidIndexes($indexes);
        foreach ($indexes as $index) {
            $this->addIndex($index);
        }
    }

    /**
     * @param \Heyday\SphinxSearch\SphinxIndexConfiguration $index
     */
    protected function addIndex(SphinxIndexConfiguration $index)
    {
        $this->indexes[$index->getIdentifier()] = $index;
    }

    /**
     * @param string $identifier
     * @return bool|SphinxIndexConfiguration
     */
    protected function getIndex($identifier)
    {
        return isset($this->indexes[$identifier]) ? $this->indexes[$identifier] : false;
    }

    /**
     * @param array|\ArrayObject $defaultDbConfig
     */
    public function setDefaultDbConfig($defaultDbConfig)
    {
        $this->assertValidDbConfig($defaultDbConfig);
        $this->defaultDbConfig = $defaultDbConfig;
    }

    /**
     * @param string $binaryLocation
     */
    public function setBinaryLocation($binaryLocation)
    {
        $this->binaryLocation = $binaryLocation;
    }

    /**
     * @param string $configFolder
     */
    public function setConfigFolder($configFolder)
    {
        $this->configFolder = $configFolder;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->assertValidHost($host);
        $this->host = $host;
    }

    /**
     * @param string $indexerMemLimit
     */
    public function setIndexerMemLimit($indexerMemLimit)
    {
        $this->indexerMemLimit = $indexerMemLimit;
    }

    /**
     * @param string $logFolder
     */
    public function setLogFolder($logFolder)
    {
        $this->logFolder = $logFolder;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->assertValidPort($port);
        $this->port = $port;
    }

    /**
     * @param string $binary
     * @return string
     */
    protected function getFullBinaryLocation($binary)
    {
        return sprintf(
            "%s/%s",
            $this->binaryLocation,
            $binary
        );
    }

    /**
     * @return string
     */
    protected function getConfigFolder()
    {
        return realpath(
            $this->configFolder
        );
    }

    /**
     * @return string
     */
    protected function getLogFolder()
    {
        return realpath(
            $this->logFolder
        );
    }

    /**
     * @return string
     */
    protected function getPidFilePath()
    {
        return sprintf(
            "%s/searchd.pid",
            $this->getLogFolder()
        );
    }

    /**
     * @return string
     */
    protected function getConfigPath()
    {
        return sprintf(
            "%s/Sphinx-%s.config",
            $this->getConfigFolder(),
            \Director::get_environment_type()
        );
    }

    /**
     * @return string
     */
    protected function getStartCommand()
    {
        return sprintf(
            "%s --config %s",
            $this->getFullBinaryLocation("searchd"),
            $this->getConfigPath()
        );
    }

    /**
     * @return string
     */
    protected function getStopCommand()
    {
        return sprintf(
            "%s --config %s --stop",
            $this->getFullBinaryLocation("searchd"),
            $this->getConfigPath()

        );
    }

    /**
     * @param array $runOptions
     * @return string
     */
    protected function getIndexCommand($runOptions)
    {
        return sprintf(
            "%s --config %s %s",
            $this->getFullBinaryLocation("indexer"),
            $this->getConfigPath(),
            implode(' ', $runOptions)
        );
    }

    /**
     * @return array
     */
    public function getDefaultDbConfig()
    {
        if ($this->defaultDbConfig instanceof \ArrayObject) {
            return $this->defaultDbConfig->getArrayCopy();
        }

        return $this->defaultDbConfig;
    }

    /**
     * @return bool|int
     */
    public function isRunning()
    {
        $pidFile = $this->getPidFilePath();

        if (file_exists($pidFile)) {
            $pid = (int) trim(file_get_contents($pidFile));

            if ($pid && preg_match("/(^|\\s)$pid\\s/m", `ps ax`)) {
                return $pid;
            }
        }

        return false;
    }

    /**
     * Generate the config file
     * @return void
     */
    public function generateConfigFile()
    {
        \Versioned::reading_stage('Live');
        
        $indexes = new \ArrayList(array_map(
            [$this, 'getIndexConfig'],
            $this->indexes
        ));

        \Config::inst()->update('SSViewer', 'source_file_comments', false);
        $viewer = new \SSViewer('Config');

        $text = $viewer->process(
            new \ArrayData([
                'Indexes' => $indexes,
                'LogFolder' => $this->getLogFolder(),
                'Port' => $this->port,
                'Host' => $this->host,
                'IndexerMemLimit' => $this->indexerMemLimit
            ])
        );

        file_put_contents($this->getConfigPath(), $text);
    }

    /**
     * @param \Heyday\SphinxSearch\SphinxIndexConfiguration $index
     * @return \ArrayData
     */
    protected function getIndexConfig(SphinxIndexConfiguration $index)
    {
        return new \ArrayData([
            'Index' => $index->getIdentifier(),
            'IndexPath' => $index->getPath($this->indexFolder),
            'Query' => str_replace('"', '`', $index->getQueryString()),
            'Attributes' => $index->getAttributes(),
            'ConfigFolder' => $this->getConfigFolder(),
            'Morphology' => $index->getMorphology(),
            'DB' => new \ArrayData($index->getDbConfig() ?: $this->getDefaultDbConfig())
        ]);
    }

    /**
     * @param array $options
     * @return array|bool
     */
    public function index($options = ['all' => true])
    {
        $this->generateConfigFile();

        $runOptions = [];

        if ($this->isRunning()) {
            $runOptions[] = '--rotate';
        }

        if (isset($options['skip'])) {
            foreach($this->indexes as $index) {
                $id = $index->getIdentifier();
                if (!in_array($id, $options['skip'])) {
                    $runOptions[] = $id;
                }
            }
        } elseif (isset($options['all']) && $options['all']) {
            $runOptions[] = '--all';
        }

        $output = [];

        exec($this->getIndexCommand($runOptions), $output);

        return $output;
    }

    /**
     * @param string $str
     * @param null $index
     * @param int $limit
     * @param bool $start
     * @return array
     */
    public function search($str = '', $index = null, $limit = 10, $start = false)
    {
        if ($this->host == '127.0.0.1' && !$this->isRunning()) {
            if ($start) {
                $this->start(false);
                \SS_Log::log(new \Exception('Tried to search without searchd running'), \SS_Log::NOTICE);
                sleep(1);

            } else {
                \SS_Log::log(new \Exception('Tried to search without searchd running'), \SS_Log::ERR);
            }

        }

        $sphinxClient = $this->getSphinxClient();

        if ($i = $this->getIndex($index)) {
            $weights = $i->getFieldWeights();
            if (is_array($weights) && count($weights)) {
                $sphinxClient->SetFieldWeights($weights);
            }
        }

        if ($limit) {
            $sphinxClient->SetLimits(0, $limit);
        }

        $result = $sphinxClient->Query($str, $index ?: '*');
        
        return $result;
    }

    /**
     * @return array|bool
     */
    public function start()
    {
        if (!$this->isRunning()) {
            $this->generateConfigFile();

            $output = [];
            exec($this->getStartCommand(), $output);

            return $output;
        }

        return false;
    }

    /**
     * @param bool $force
     * @return array|bool
     */
    public function stop($force = false)
    {
        if ($this->isRunning() || $force) {
            $this->generateConfigFile();

            $output = [];
            exec($this->getStopCommand(), $output);

            return $output;
        }

        return false;
    }

    /**
     * @return \Sphinx\SphinxClient
     */
    public function getSphinxClient()
    {
        $sphinxClient = new SphinxClient();

        $sphinxClient->SetServer(
            $this->host,
            $this->port
        );

        $sphinxClient->SetMatchMode(SPH_MATCH_ANY);
        $sphinxClient->SetSortMode(SPH_SORT_RELEVANCE);

        return $sphinxClient;
    }
}


