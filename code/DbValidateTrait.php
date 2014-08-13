<?php

namespace Heyday\SphinxSearch;

/**
 * @package Heyday\SphinxSearch
 */
trait DbValidateTrait
{
    /**
     * @param array|\ArrayObject $dbConfig
     * @throws \RuntimeException
     */
    protected function assertValidDbConfig($dbConfig)
    {
        $requiredKeys = [
            'server',
            'username',
            'password',
            'database',
            'port'
        ];

        foreach ($requiredKeys as $key) {
            if (empty($dbConfig[$key])) {
                throw new \RuntimeException(sprintf(
                    "Db config requires '%s' to be provided",
                    $key
                ));
            }
        }
    }
} 