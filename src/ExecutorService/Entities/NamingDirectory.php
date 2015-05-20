<?php

/**
 * \AppserverIo\Concurrency\ExecutorService\Entities\NamingDirectory
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/appserver
 * @link      http://www.appserver.io
 */

namespace AppserverIo\Concurrency\ExecutorService\Entities;

use AppserverIo\Concurrency\ExecutorService\Entities\Storage;

/**
 * Naming directory implementation.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/appserver
 * @link      http://www.appserver.io
 *
 * @property string $scheme The binding string scheme
 */
class NamingDirectory extends Storage
{
    /**
     * Binds the passed instance with the name to the naming directory.
     *
     * @param string $name  The name to bind the value with
     * @param mixed  $value The object instance to bind
     * @param array  $args  The array with the arguments
     *
     * @return void
     * @throws \AppserverIo\Psr\Naming\NamingException Is thrown if the value can't be bound ot the directory
     */
    public function bind($name, $value, array $args = array())
    {
    
        // strip off the schema
        $name = str_replace(sprintf('%s:', $this->getScheme()), '', $name);
    
        // tokenize the name
        $token = strtok($name, '/');
    
        // while we've tokens, try to find the appropriate subdirectory
        while ($token !== false) {
            // check if we can find something
            if ($this->hasAttribute($token)) {
                // load the data bound to the token
                $data = $this->getAttribute($token);
    
                // load the bound value/args
                list ($valueFound, ) = $data;
    
                // try to bind it to the subdirectory
                if ($valueFound instanceof NamingDirectoryInterface) {
                    return $valueFound->bind(str_replace($token . '/', '', $name), $value, $args);
                }
    
                // throw an exception if we can't resolve the name
                throw new \Exception(sprintf('Cant\'t bind %s to value of naming directory %s', $token, $this->getIdentifier()));
    
            } else {
                // bind the value
                return $this->setAttribute($token, array($value, $args));
            }
    
            // load the next token
            $token = strtok('/');
        }
    
        // throw an exception if we can't resolve the name
        throw new \Exception(sprintf('Cant\'t bind %s to naming directory %s', $token, $this->getIdentifier()));
    }
    
    /**
     * Binds the passed callback with the name to the naming directory.
     *
     * @param string   $name     The name to bind the callback with
     * @param callable $callback The callback to be invoked when searching for
     * @param array    $args     The array with the arguments passed to the callback when executed
     *
     * @return void
     * @see \AppserverIo\Appserver\Naming\NamingDirectory::bind()
     */
    public function bindCallback($name, callable $callback, array $args = array())
    {
        $this->bind($name, $callback, $args);
    }
    
    /**
     * Binds a reference with the passed name to the naming directory.
     *
     * @param string $name      The name to bind the reference with
     * @param string $reference The name of the reference
     *
     * @return void
     * @see \AppserverIo\Appserver\Naming\NamingDirectory::bind()
     */
    public function bindReference($name, $reference)
    {
        $this->bindCallback($name, array(&$this, 'search'), array($reference, array()));
    }
    
    /**
     * Queries the naming directory for the requested name and returns the value
     * or invokes the bound callback.
     *
     * @param string $name The name of the requested value
     * @param array  $args The arguments to pass to the callback
     *
     * @return mixed The requested value
     * @throws \AppserverIo\Psr\Naming\NamingException Is thrown if the requested name can't be resolved in the directory
     */
    public function search($name, array $args = array())
    {
    
        // strip off the schema
        $name = str_replace(sprintf('%s:', $this->getScheme()), '', $name);
    
        // tokenize the name
        $token = strtok($name, '/');
    
        // while we've tokens, try to find a value bound to the token
        while ($token !== false) {
            // check if we can find something
            if ($this->hasAttribute($token)) {
                // load the value
                $found = $this->getAttribute($token);
    
                // load the binded value/args
                list ($value, $bindArgs) = $found;
    
                // check if we've a callback method
                if (is_callable($value)) {
                    // if yes, merge the params and invoke the callback
                    foreach ($args as $arg) {
                        $bindArgs[] = $arg;
                    }
                    // invoke the callback
                    return call_user_func_array($value, $bindArgs);
                }
    
                // search recursive
                if ($value instanceof NamingDirectoryInterface) {
                    if ($value->getName() !== $name) {
                        // if $value is NOT what we're searching for
                        return $value->search(str_replace($token . '/', '', $name), $args);
                    }
                }
    
                // if not, simply return the value/object
                return $value;
            }
    
            // load the next token
            $token = strtok('/');
        }
    
        // delegate the search request to the parent directory
        if ($parent = $this->getParent()) {
            return $parent->search($name, $args);
        }
    
        // throw an exception if we can't resolve the name
        throw new \Exception(sprintf('Cant\'t resolve %s in naming directory %s', ltrim($name, '/'), $this->getIdentifier()));
    }

    /**
     * Initialize the directory with a name and the parent one.
     *
     * @param string                                           $name   The directory name
     * @param \AppserverIo\Psr\Naming\NamingDirectoryInterface $parent The parent directory
     */
    public function __construct($name = null, $parent = null)
    {
        // initialize the members
        $this->parent = $parent;
        $this->name = $name;
        $this->scheme = 'PHP';
    }

    /**
     * Returns the directory name.
     *
     * @return string The directory name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the parend directory.
     *
     * @return \AppserverIo\Psr\Naming\NamingDirectoryInterface
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set the scheme, php or http for example
     *
     * @param string $scheme The scheme we want to use
     *
     * @return void
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
    }

    /**
     * Returns the scheme.
     *
     * @return string The scheme we want to use
     */
    public function getScheme()
    {

        // if the parent directory has a schema, return this one
        if ($parent = $this->getParent()) {
            return $parent->getScheme();
        }

        // return our own schema
        return $this->scheme;
    }

    /**
     * The unique identifier of this directory. That'll be build up
     * recursive from the scheme and the root directory.
     *
     * @return string The unique identifier
     * @see \AppserverIo\Storage\StorageInterface::getIdentifier()
     *
     * @throws \AppserverIo\Psr\Naming\NamingException
     */
    public function getIdentifier()
    {

        // check if we've a parent directory
        if ($parent = $this->getParent()) {
            return $parent->getIdentifier() . '/' . $this->getName();
        }

        // if not, we MUST have a scheme, because we're root
        if ($scheme = $this->getScheme()) {
            return $scheme . ':' . $this->getName();
        }

        // the root node needs a scheme
        throw new \Exception(sprintf('Missing scheme for naming directory', $this->getName()));
    }

    /**
     * Returns the value with the passed name from the context.
     *
     * @param string $key The key of the value to return from the context.
     *
     * @return mixed The requested attribute
     * @see \AppserverIo\Psr\Context\ContextInterface::getAttribute()
     */
    public function getAttribute($key)
    {
        return $this->get($key);
    }

    /**
     * Queries if the attribute with the passed key is bound.
     *
     * @param string $key The key of the attribute to query
     *
     * @return boolean TRUE if the attribute is bound, else FALSE
     */
    public function hasAttribute($key)
    {
        return $this->has($key);
    }

    /**
     * Sets the passed key/value pair in the directory.
     *
     * @param string $key   The attributes key
     * @param mixed  $value Tha attribute to be bound
     *
     * @return void
     */
    public function setAttribute($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Returns the keys of the bound attributes.
     *
     * @return array The keys of the bound attributes
     */
    public function getAllKeys()
    {
        return array_keys($this->all());
    }

    /**
     * Create and return a new naming subdirectory with the attributes
     * of this one.
     *
     * @param string $name   The name of the new subdirectory
     * @param array  $filter Array with filters that will be applied when copy the attributes
     *
     * @return \AppserverIo\Appserver\Naming\NamingDirectory The new naming subdirectory
     */
    public function createSubdirectory($name, array $filter = array())
    {

        // create a new subdirectory instance
        $subdirectory = new NamingDirectory($name, $this);

        // copy the attributes specified by the filter
        if (sizeof($filter) > 0) {
            foreach ($this->getAllKeys() as $key => $value) {
                foreach ($filter as $pattern) {
                    if (fnmatch($pattern, $key)) {
                        $subdirectory->bind($key, $value);
                    }
                }
            }
        }

        // bind it the directory
        $this->bind($name, $subdirectory);

        // return the instance
        return $subdirectory;
    }
}
