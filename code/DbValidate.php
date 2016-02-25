<?php

namespace Heyday\SphinxSearch;

/**
 * Class DbValidate
 * @package Heyday\SphinxSearch
 */
class DbValidate
{
    /**
     * @param array|\ArrayObject $dbConfig
     * @throws \RuntimeException
     */
    public function assertValidDbConfig($dbConfig)
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