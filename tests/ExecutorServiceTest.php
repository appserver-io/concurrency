<?php

/**
 * \AppserverIo\Concurrency\ExecutorServiceTest
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

namespace AppserverIo\Concurrency;

use AppserverIo\Concurrency\ExecutorService as ExS;

/**
 * Tests for executor service
 *
 * @category  Library
 * @package   Concurrency
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2014 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/concurrency
 * @link      http://www.appserver.io
 */
class ExecutorServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    public function setUp()
    {
        // init ExecutorService
        ExS\Core::init();
    }
    
    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    public function tearDown()
    {
        // shutdown ExecutorService
        ExS\Core::shutdown();
    }
    
    /**
     * Test if executor service global initialisation works
     *
     * @return void
     */
    public function testInit()
    {
        $this->assertInstanceOf(
            '\AppserverIo\Concurrency\ExecutorService',
            $GLOBALS[ExS\Core::getGlobalVarName()]
        );
    }
    
    /**
     * Test if new entity creation works
     * 
     * @return void
     */
    public function testCreateNewFromEntityAndCheckIfIts()
    {
        $storage = ExS\Core::newFromEntity('\AppserverIo\Concurrency\ExecutorService\Entities\Storage');
        $storage->set('test', 'test');
        
    }
}
