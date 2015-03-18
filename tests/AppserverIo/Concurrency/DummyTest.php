<?php

/**
 * \AppserverIo\Concurrency\DummyTest
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

/**
 * Dummy tests
 *
 * @category  Library
 * @package   Concurrency
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2014 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/concurrency
 * @link      http://www.appserver.io
 */
class DummyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Dummy test
     *
     * @return void
     */
    public function testDummy()
    {
        // dummy test
        $this->assertTrue(true);
    }
}
