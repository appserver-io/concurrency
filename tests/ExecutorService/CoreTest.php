<?php

/**
 * \AppserverIo\Concurrency\ExecutorService\CoreTest
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
 * Tests for executor service core functionality
 *
 * @category  Library
 * @package   Concurrency
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2014 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/concurrency
 * @link      http://www.appserver.io
 */
class CoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    public function tearDown()
    {
        // shutdown manually if test leaves the executor service running
        // if this is not happen, the test suite will not exit its process
        // because the executor service thread loop is still running
        if (isset($GLOBALS[ExS\Core::getGlobalVarName()])) {
            // shut it down
            ExS\Core::shutdown();
        }
    }
    
    /**
     * Test if global var name getter work as expected
     *
     * @return void
     */
    public function testGlobalVarNameFunctionReturnsCorrectValue()
    {
        $globalVar = ExS\Core::getGlobalVarName();
        $this->assertSame($globalVar[0] . $globalVar[1], '__');
    }

    /**
     * Test if init function does its job
     * 
     * @return void
     */
    public function testInitFunctionDoesItsJob()
    {
        $this->assertFalse(isset($GLOBALS[ExS\Core::getGlobalVarName()]));
        ExS\Core::init();
        $this->assertTrue(isset($GLOBALS[ExS\Core::getGlobalVarName()]));
        $this->assertInstanceOf('AppserverIo\Concurrency\ExecutorService', $GLOBALS[ExS\Core::getGlobalVarName()]);
    }

    /**
     * Test if everything is clean after shutdown when executor service was 
     * initialised before.
     * 
     * @return void
     */
    public function testShutdownFunctionWithoutSpecificEntityPreInitialised()
    {
        ExS\Core::init();
        ExS\Core::shutdown();
        $this->assertFalse(isset($GLOBALS[ExS\Core::getGlobalVarName()]));
    }

    /**
     * Test if everything is clean after shutdown when executor service was 
     * not initialised before.
     *
     * @expectedException              \AppserverIo\Concurrency\ExecutorService\Exception
     * @expectedExceptionMessageRegExp /Failed to get instance '.*'\. Please call init\(\) in global scope first and check if PTHREADS_ALLOW_GLOBALS/
     * 
     * @return void
     */
    public function testShutdownFunctionWithoutSpecificEntityNotInitialised()
    {
        ExS\Core::shutdown();
    }
    
    /**
     * Test newFromEntity function non aliase not initialised
     *
     * @return void
     */
    public function testNewFromEntityFunctionNonAliasInitialised()
    {
        ExS\Core::init();
        $stdClassExS = ExS\Core::newFromEntity('\stdClass');
        $this->assertInstanceOf('\AppserverIo\Concurrency\ExecutorService', $stdClassExS);
    }
    
    /**
     * Test newFromEntity function aliase not initialised
     *
     * @return void
     */
    public function testNewFromEntityFunctionAliasInitialised()
    {
        ExS\Core::init();
        $stdClassExS = ExS\Core::newFromEntity('\stdClass', 'entity-01');
        $this->assertInstanceOf('\AppserverIo\Concurrency\ExecutorService', $stdClassExS);
    }
    
    /**
     * Test newFromEntity function with duplicate classname non alias initialised
     *
     * @expectedException              \AppserverIo\Concurrency\ExecutorService\Exception
     * @expectedExceptionMessageRegExp /Entity '\\stdClass' has already been created\./
     *
     * @return void
     */
    public function testNewFromEntityFunctionWithIdenticalClassNameNonAliasInitialised()
    {
        ExS\Core::init();
        $stdClassExS = ExS\Core::newFromEntity('\stdClass');
        $stdClassExS = ExS\Core::newFromEntity('\stdClass');
    }
    
    /**
     * Test newFromEntity function with duplicate classname alias initialised
     *
     * @expectedException              \AppserverIo\Concurrency\ExecutorService\Exception
     * @expectedExceptionMessageRegExp /Entity '\\stdClass' with alias 'entity-01' has already been created\./
     *
     * @return void
     */
    public function testNewFromEntityFunctionWithIdenticalClassNameAliasInitialised()
    {
        ExS\Core::init();
        $stdClassExS = ExS\Core::newFromEntity('\stdClass', 'entity-01');
        $stdClassExS = ExS\Core::newFromEntity('\stdClass', 'entity-01');
    }

    /**
     * Test newFromEntity function not initialised
     *
     * @expectedException              \AppserverIo\Concurrency\ExecutorService\Exception
     * @expectedExceptionMessageRegExp /Failed to get instance '.*'\. Please call init\(\) in global scope first and check if PTHREADS_ALLOW_GLOBALS/
     *
     * @return void
     */
    public function testNewFromEntityFunctionNotInitialised()
    {
        ExS\Core::newFromEntity('\stdClass');
    }
    
    /**
     * Test shutdown function with specific entity no alias pre initialised
     * 
     * @return void
     */
    public function testShutdownFunctionWithSpecificEntityNoAliasPreInitialised()
    {
        ExS\Core::init();
        ExS\Core::newFromEntity('\stdClass');
        ExS\Core::shutdown('\stdClass');
        $this->assertTrue(isset($GLOBALS[ExS\Core::getGlobalVarName()]));
    }
    
    /**
     * Test shutdown function with specific entities aliased pre initialised
     * 
     * @return void
     */
    public function testShutdownFunctionWithSpecificEntitiesAliasedPreInitialised()
    {
        ExS\Core::init();
        for ($i=1; $i<=10; $i++) {
            ExS\Core::newFromEntity('\stdClass', 'e-' . $i);
        }
        for ($i=1; $i<=10; $i++) {
            ExS\Core::shutdown('e-' . $i);
        }
    }

    /**
     * Test getInstance function not initialised
     *
     * @expectedException              \AppserverIo\Concurrency\ExecutorService\Exception
     * @expectedExceptionMessageRegExp /Failed to get instance '.*'\. Please call init\(\) in global scope first and check if PTHREADS_ALLOW_GLOBALS/
     *
     * @return void
     */
    public function testGetInstanceNotInitialised()
    {
        ExS\Core::getInstance();
    }

    /**
     * Test getInstance function initialised
     * 
     * @return void
     */
    public function testGetInstanceInitialised()
    {
        ExS\Core::init();
        $exS = ExS\Core::getInstance();
        $this->assertInstanceOf('\AppserverIo\Concurrency\ExecutorService', $exS);
    }

    /**
     * Test if getEntityKey returns correct key
     * 
     * @return void
     */
    public function testGetEntityKeyFunctionReturnsCorrectKey()
    {
        $testKey = 'storage';
        $this->assertSame(ExS\Core::ENTITY_KEY_PREFIX . $testKey, ExS\Core::getEntityKey($testKey));
    }

    /**
     * Test getEntity function when entity was not created before and ExS was initialised
     *
     * @expectedException              \AppserverIo\Concurrency\ExecutorService\Exception
     * @expectedExceptionMessageRegExp /Entity 'storage' does not exist\./
     *
     * @return void
     */
    public function testGetEntityNotCreatedBeforeInitialised()
    {
        ExS\Core::init();
        $entity = ExS\Core::getEntity('storage');
    }
    
    /**
     * Test getEntity function when created before and ExS was initialised
     * 
     * @return void
     */
    public function testGetEntityCreatedBeforeInitialised()
    {
        $entityType = '\AppserverIo\Concurrency\ExecutorService\Entities\Storage';
        ExS\Core::init();
        ExS\Core::newFromEntity($entityType, 'storage');
        $exSentity = ExS\Core::getEntity('storage');
        $this->assertInstanceOf('\AppserverIo\Concurrency\ExecutorService', $exSentity);
    }
}
