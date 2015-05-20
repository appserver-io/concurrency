<?php

/**
 * \AppserverIo\Concurrency\ExecutorService\EntitiesExecutor\NamingDirectory
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category  Library
 * @package   Concurrency
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2014 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/concurrency
 * @link      http://www.appserver.io
 */

namespace AppserverIo\Concurrency\ExecutorService\Entities;

use AppserverIo\Concurrency\ExecutorServiceTestCase;
use AppserverIo\Concurrency\ExecutorService as ExS;

/**
 * Tests for entity naming directory 
 *
 * @category  Library
 * @package   Concurrency
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2014 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/concurrency
 * @link      http://www.appserver.io
 */
class NamingDirectoryTest extends ExecutorServiceTestCase
{
    /**
     * Tests entity if execution of command createSubdirectory and search works
     */
    public function testEntityExecutionCreateSubdirectoryWithSearch()
    {
        $entityType = '\AppserverIo\Concurrency\ExecutorService\Entities\NamingDirectory';
        $namingDirectory = ExS\Core::newFromEntity($entityType, 'namingDirectory');
        $namingDirectory->createSubdirectory('test');
        $testDir = $namingDirectory->search('test');
        $this->assertInstanceOf('\AppserverIo\Concurrency\ExecutorService\Entities\NamingDirectory', $testDir);
    }
}
