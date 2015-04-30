<?php

/**
 * \AppserverIo\Concurrency\ExecutorService
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
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io-lab/storage
 * @link      http://www.appserver.io
 */

namespace AppserverIo\Concurrency;

/**
 * Class ExecutorService
 *
 * An executor service that can be used to handle single object instance, asynchronous method calls etc...
 *
 * @category  Library
 * @package   Concurrency
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io-lab/storage
 * @link      http://www.appserver.io
 */
class ExecutorService extends \Thread
{
    /**
     * Defines the entity key prefix for internal key storage
     *
     * @var string
     */
    const ENTITY_KEY_PREFIX = '__entity/';
    
    /**
     * Defines the execution method types
     *
     * @var string
     */
    const EXECUTE_METHOD_DEFAULT = '__execute';
    const EXECUTE_METHOD_ASYNCHRONOUS = '__executeAsynchronous';
    const EXECUTE_METHOD_SYNCHRONIZED = '__executeSynchronized';
    
    /**
     * Defines internal command for entity and executor service handling
     *
     * @var string
     */
    const EXECUTE_CMD_ENTITY_RETURN = '__return';
    const EXECUTE_CMD_ENTITY_INVOKE = '__invoke';
    const EXECUTE_CMD_ENTITY_RESET = '__reset';
    const EXECUTE_CMD_SHUTDOWN = '__shutdown';

    
    /**
     * Contructor
     *
     * @param string $entityType The entity type class to use as entity object
     * @param string $autoloader The path to the autoloaders class to require
     * @param string $startFlags The start flags used for starting threads
     */
    public function __construct($entityType, $autoloader = null, $startFlags = null)
    {
        // init properties
        $this->__callbackAllowed = false;
        $this->__entityType = $entityType;
        $this->__autoloader = $autoloader;
        
        // init entity
        $this->__initEntityAnnotations($entityType);
        $this->__entityInstance = new $entityType();
        
        // init start flags
        if (is_null($startFlags)) {
            $startFlags = PTHREADS_INHERIT_ALL | PTHREADS_ALLOW_GLOBALS;
        }
        
        // start thread routine
        $this->start($startFlags);
    }
    
    /**
     * Returns the entities key that will be used for storing internal refs of instance
     *
     * @param  string $key The key for entity prefix to append
     * @return string
     */
    public static function __getEntityKey($key)
    {
        return self::ENTITY_KEY_PREFIX . $key;
    }
    
    
    /**
     * Return a valid variable name to be set as global variable
     * based on own class name with automatic namespace cutoff
     *
     * @static
     * @return string
     */
    public static function __getGlobalVarName()
    {
        $cn = __CLASS__;
        return '__' . strtolower(substr($cn, strrpos($cn, '\\')+(int)($cn[0] === '\\')));
    }
    
    /**
     * Initialises the global storage.
     * This fuction should be called on global scope.
     *
     * @param string $autoloader The path to the autoloaders class to require
     * @param string $mainEntity The entity to use for the main instance
     *
     * @static
     * @return void
     */
    public static function __init($autoloader = null, $mainEntity = '\stdClass')
    {
        $globalVarName = self::__getGlobalVarName();
        global $$globalVarName;
        if (is_null($$globalVarName)) {
            return $$globalVarName = new self($mainEntity, $autoloader);
        }
    }
    
    /**
     * Returns the instance created in global scope
     *
     * @static
     * @return GlobalStorage The global storage instance
     */
    public static function __getInstance()
    {
        $globalVarName = self::__getGlobalVarName();
        global $$globalVarName;
        if (is_null($$globalVarName)) {
            throw new \Exception(sprintf("Failed to get instance '$%s'. Please call init() in global scope first and check if PTHREADS_ALLOW_GLOBALS flag is set in specific Thread start calls.", $globalVarName));
        }
        return $$globalVarName;
    }
    
    /**
     * Returns an entity from internal storage
     *
     * @param string $entityTypeOrAlias The type or aliase to get
     *
     * @throws \Exception
     * @return ExecutorService
     */
    public static function __getEntity($entityTypeOrAlias)
    {
        // get own instance
        $self = self::__getInstance();
        // init entityKey
        $entityKey = self::__getEntityKey($entityTypeOrAlias);
        // check if entity does not exists
        if (!isset($self["{$entityKey}"])) {
            throw new \Exception(sprintf("Entity '%s' does not exist.", $entityTypeOrAlias));
        }
        // return it
        return $self["{$entityKey}"];
    }
    
    /**
     * Creates a new instance of execution service with given entity class and mapped alias
     *
     * @param string $entityType The class to make use of execution service as singleton
     * @param string $alias      The alias to use for store that instance in global storage of execution service
     * @param string $autoloader The autoloader to use
     *
     * @return ExecutorService
     * @throws \Exception
     */
    public static function __newFromEntity($entityType, $alias = null, $autoloader = null)
    {
        // get own instance
        $self = self::__getInstance();
        // init entity key
        $entityKey = self::__getEntityKey($entityType);
        // init autoloader and set default if nothing was given
        if (is_null($autoloader)) {
            $autoloader = $self->__autoloader;
        }
        
        // check alias functionality
        if ($alias) {
            $entityKey = self::__getEntityKey($alias);
            // check if alias was registered already
            if (isset($self["{$entityKey}"])) {
                throw new \Exception(sprintf("Entity '%s' with alias '%s' has already been created.", $entityType, $alias));
            }
        } else {
            // check if entity was registered already
            if (isset($self["{$entityKey}"])) {
                throw new \Exception(sprintf("Entity '%s' has already been created.", $entityType, $alias));
            }
        }
        
        // create execution service instance with entity.
        $newInstanceFromEntity = new self($entityType, $autoloader);
        
        // set ref to local storage
        $self["{$entityKey}"] = $newInstanceFromEntity;
        
        // return it
        return $newInstanceFromEntity;
    }
    
    /**
     * Registeres a callback for asynch methods to be called after execution
     *
     * @param callable $callback The callback function
     *
     * @return ExecutorService
     */
    public function __callback(callable $callback)
    {
        if ($this->__callbackAllowed === true) {
            $this->synchronized(
                function ($self, $callback) {
                    // copy closure to thread object
                    $self->callback = $callback;
                    $self->__callbackAllowed = false;
                },
                $this,
                $callback
            );
        } else {
            throw new \Exception("Not allowed to set a callback function right now...");
        }
        // return executor service
        return $this;
    }
    
    /**
     * Executes given command with args in a default non locked way
     *
     * @param string  $cmd   The command to execute
     * @param string  $args  The arguments for the command to be executed
     * @param boolean $async Wheater the method should be executed asynchronously or not
     *
     * @return mixed The return value we got from execution
     */
    public function __execute($cmd, array $args = array(), $async = false)
    {

        // check if execution is going on or startup is not ready yet
        if ($this->run !== false) {
            // maybe it make sense to throw an exception in this case...
            
            // wait while execution is running
            while ($this->run !== false) {
                // sleep a little while waiting loop
                usleep(100);
            }
        }

        // init closure var
        $closure = null;
        // check if first argument is a closure
        if (isset($args[0]) && is_callable($args[0])) {
            // get closure definition
            $closure = $args[0];
            // clear args array for closure execution on entity
            $args = array();
        }
        
        // synced communication call
        $this->synchronized(
            function ($self, $cmd, $args, $closure) {
                // set run flag to be true cause we wanna run now
                $self->run = true;
                // set command and argument values
                $self->cmd = $cmd;
                $self->args = $args;
                $self->closure = $closure;
                // notify to start execution
                $self->notify();
            },
            $this,
            $cmd,
            $args,
            $closure
        );
        
        // check if function should be called async or not
        if ($async) {
            // do not wait and return executor service for being able to
            // provide a callback function via ->__callback(...)
            $this->__callbackAllowed = true;
            return $this;
        }
        
        // wait while execution is running
        while ($this->run !== false) {
            // sleep a little while waiting loop
            usleep(100);
        }
        
        // check if an exceptions was thrown and throw it again in this context.
        if ($this->exception) {
            throw $this->exception;
        }
        
        // return the return value we got from execution
        return $this->return;
    }
    
    /**
     * Executes the given command and arguments in a synchronized way.
     *
     * This function is intend to be protected to make use of automatic looking
     * when calling this function to avoid race conditions and dead-locks.
     * This means this function can not be called simultaneously.
     *
     * @param string $cmd  The command to execute
     * @param string $args The arguments for the command to be executed
     *
     * @return mixed The return value we got from execution
     */
    protected function __executeSynchronized($cmd, array $args = array())
    {
        // call normal execute function
        return $this->__execute($cmd, $args);
    }

    /**
     * Executes the given command and arguments in an asynchronous way.
     *
     * It will return a promise object which can be used for further callback processing.
     *
     * @param string $cmd  The command to execute
     * @param string $args The arguments for the command to be executed
     *
     * @return mixed The return value we got from execution
     */
    protected function __executeAsynchronous($cmd, array $args = array())
    {
        // call execute function to be async
        return $this->__execute($cmd, $args, true);
    }
    
    /**
     * Introduce a magic __call function to delegate all methods to the internal
     * execution functionality. If you hit a Method which is not available in executor
     * logic, it will throw an exception as you would get a fatal error if you want to call
     * a function on undefined object.
     *
     * @param string $methodname The methodname to execute
     * @param string $args       The arguments for the command to be executed
     *
     * @return mixed The return value we got from execution
     */
    public function __call($methodname, $args)
    {
        $executeTypeFunction = self::EXECUTE_METHOD_DEFAULT;
        // check method execution type from mapper
        if (isset($this->methodExecutionTypeMapper["::{$methodname}"])) {
            $executeTypeFunction = $this->methodExecutionTypeMapper["::{$methodname}"];
        }
        return $this->$executeTypeFunction($methodname, $args);
    }

    
    /**
     * Initializes all entity method annotations
     *
     * @param  string $class The class name
     * @return array
     */
    public function __initEntityAnnotations($class)
    {
        // get reflection of entity class
        $reflector = new \ReflectionClass($class);
        // get all Methods
        $methods = $reflector->getMethods();
        // init method exec type mapper array
        $methodExecutionTypeMapper = array();
        // iterate all methods
        foreach ($methods as $method) {
            // set default method type
            $methodExecutionTypeMapper["::{$method->getName()}"] =  self::EXECUTE_METHOD_DEFAULT;
            // get method annotations
            preg_match_all('#@(.*?)\n#s', $method->getDocComment(), $annotations);
            // check if asynch annotation
            if (in_array('Asynchronous', $annotations[1])) {
                $methodExecutionTypeMapper["::{$method->getName()}"] = self::EXECUTE_METHOD_ASYNCHRONOUS;
            }
            // check if synch annotation
            if (in_array('Synchronized', $annotations[1])) {
                $methodExecutionTypeMapper["::{$method->getName()}"] = self::EXECUTE_METHOD_SYNCHRONIZED;
            }
        }
        // set mapper array
        $this->methodExecutionTypeMapper = $methodExecutionTypeMapper;
    }
    
    /**
     * The main thread routine function
     *
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        // register autoloader if exists
        if (!is_null($this->__autoloader)) {
            require $this->__autoloader;
        }
        
        // set initial param values
        $this->return = null;
        $this->exception = null;
        // set shutdown flag internally so that its only possible change it via shutdown command
        $shutdown = false;
        
        // get entity properties to local var ref
        $entityInstance = $this->__entityInstance;
        $entityType = $this->__entityType;
        
        // loop while no shutdown command was sent
        do {
            // synced communication call
            $this->synchronized(
                function ($self) {
                    // set initial param values
                    $this->cmd = null;
                    $this->args = array();
                    $this->closure = null;
                    $this->callback = null;
                    $self->run = false;
                    $self->wait();
                    // reset return and exception properties
                    $this->exception = null;
                    $this->return = null;
                },
                $this
            );
            
            // try to execute given command
            try {
                // first check internal commands before delegate commands to entity itself
                switch ($this->cmd) {
                    // in case of invalid type
                    case null:
                        throw new \Exception("No valid command '%s' sent.", var_export($this->cmd, true));
                        break;
                    
                    // in case of returning entity itself
                    case self::EXECUTE_CMD_ENTITY_RETURN:
                        $this->return = $entityInstance;
                        break;
                        
                    // in case of returning entity itself
                    case self::EXECUTE_CMD_ENTITY_RESET:
                        unset($entityInstance);
                        $this->__initEntityAnnotations($entityType);
                        $this->__entityInstance = $entityInstance = new $this->__entityType();
                        $this->return = true;
                        break;
                        
                    // in case of execute closure internally
                    case self::EXECUTE_CMD_ENTITY_INVOKE:
                        $callable = $this->closure;
                        if ($callable) {
                            $this->return = $callable($entityInstance);
                        }
                        $this->return = null;
                        break;
                        
                    // in case of shutdown execution service
                    case self::EXECUTE_CMD_SHUTDOWN:
                        $shutdown = true;
                        break;
                        
                    // delegate all other commands to entity itself by default
                    default:
                        // try to execute given command with arguments
                        $this->return = call_user_func_array(array(&$entityInstance, $this->cmd), $this->args);
                        
                        // check if promises are given
                        if ($this->callback) {
                            $cb = $this->callback;
                            // set return value from deferred resolver
                            $this->return = &$cb($this->return);
                            // prevent others to register callbacks on non asynch function workflow.
                            $this->__callbackAllowed = false;
                        }
                }
                
            } catch (\Exception $e) {
                // catch and hold all exceptions throws while processing for further usage
                $this->exception = $e;
            }
            // loop until shutdown
        } while ($shutdown === false);
        
        // init properties before shuting down in synced call
        $this->synchronized(
            function ($self) {
                // set initial param values
                $self->cmd = null;
                $self->args = array();
                $self->closure = null;
                $self->callback = null;
                $self->run = false;
                $self->exception = null;
                $self->return = null;
            },
            $this
        );
        
    }
}
