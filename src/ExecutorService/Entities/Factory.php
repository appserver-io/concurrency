<?php

/**
 * AppserverIo\Concurrency\ExecutorService\Entities\Factory
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io-lab/bootstrap
 * @link      http://www.appserver.io
 */

namespace AppserverIo\Concurrency\ExecutorService\Entities;

/**
 * Sample factory implementation for execution service usage
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io-lab/bootstrap
 * @link      http://www.appserver.io
 */
class Factory
{
    /**
     * Defines internal data storage
     *
     * @var array
     */
    public $instances = array();
    
    /**
     * Returns or creates an instance of given classname
     *
     * @return array
     * @Synchronized
     */
    public function get($className)
    {
        if (!isset($this->instances[$className])) {
            $this->instances[$className] = new $className();
        }
        return $this->instances[$className];
    }
}
