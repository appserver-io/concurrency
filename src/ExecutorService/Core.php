<?php

namespace AppserverIo\Concurrency\ExecutorService;

use AppserverIo\Concurrency\ExecutorService;
use AppserverIo\Concurrency\AppserverIo\Concurrency;

/**
 * Provides the core functionality for executor service instances
 *
 * @author Johann Zelger <jz@appserver.io>
 */
class Core
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
    const EX_METHOD_DEFAULT = '__execute';
    const EX_METHOD_ASYNCHRONOUS = '__executeAsynchronous';
    const EX_METHOD_SYNCHRONIZED = '__executeSynchronized';
    
    /**
     * Defines internal commands for entity management
     *
     * @var string
     */
    const EX_CMD_ENTITY_RETURN = '__return';
    const EX_CMD_ENTITY_INVOKE = '__invoke';
    const EX_CMD_ENTITY_RESET = '__reset';
    
    /**
     * Defines internal commands for executor service handling
     *
     * @var string
     */
    const EX_CMD_INIT = '__init';
    const EX_CMD_SHUTDOWN = '__shutdown';
    const EX_CMD_CALLBACK = '__callback';

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
    public static function init($autoloader = null, $mainEntity = '\stdClass')
    {
        $globalVarName = self::getGlobalVarName();
        global $$globalVarName;
        if (is_null($$globalVarName)) {
            return $$globalVarName = new ExecutorService($mainEntity, $autoloader);
        }
    }

    /**
     * Shutdown the executor service and all its registered entities
     *
     * @param string $entityTypeOrAlias The entity type or alias to shutdown. If null main shutdown will be triggered
     *
     * @static
     * @return void
     */
    public static function shutdown($entityTypeOrAlias = null)
    {
        // get own instance
        $self = self::getInstance();
        // check if entityKey is null to do a full shutdown
        if (is_null($entityTypeOrAlias)) {
            // iterate all registered entities execute shutdown
            foreach ($self as $entityKey => $entityInstance) {
                // check if it is a real entity key
                if (strpos($entityKey, self::ENTITY_KEY_PREFIX) !== false) {
                    // set shutdown signal
                    $entityInstance->__execute(self::EX_CMD_SHUTDOWN);
                }
            }
            // wait for all entity instance to shutdown properly
            foreach ($self as $entityKey => $entityInstance) {
                // check if it is a real entity key
                if (strpos($entityKey, self::ENTITY_KEY_PREFIX) !== false) {
                    // wait for proper shutdown
                    $entityInstance->join();
                    // delete ref from internal storage
                    unset($self["{$entityKey}"]);
                }
            }
            // shutdown own instance
            $self->__execute(self::EX_CMD_SHUTDOWN);
            // remove globals ref for itself
            unset($GLOBALS[self::getGlobalVarName()]);
            // wait for itself
            return $self->join();
        }
        // in every other case just shutdown the entity given as entityKey
        if ($entityInstance = self::getEntity($entityTypeOrAlias)) {
            $entityInstance->__execute(self::EX_CMD_SHUTDOWN);
            // wait for instance propert shutdown
            $entityInstance->join();
            // delete ref from internal storage
            $entityKey = self::getEntityKey($entityTypeOrAlias);
            unset($self["{$entityKey}"]);
        }
    }

    /**
     * Return a valid variable name to be set as global variable
     * based on own class name with automatic namespace cutoff
     *
     * @static
     * @return string
     */
    public static function getGlobalVarName()
    {
        return '__' . strtolower(__CLASS__);
    }

    /**
     * Returns an entity from internal storage
     *
     * @param string $entityTypeOrAlias The type or aliase to get
     *
     * @throws \AppserverIo\Concurrency\ExecutorService\Exception
     * @return ExecutorService
     */
    public static function getEntity($entityTypeOrAlias)
    {
        // get own instance
        $self = self::getInstance();
        // init entityKey
        $entityKey = self::getEntityKey($entityTypeOrAlias);
        // check if entity does not exists
        if (!isset($self["{$entityKey}"])) {
            throw new ExecutorService\Exception(sprintf("Entity '%s' does not exist.", $entityTypeOrAlias));
        }
        // return it
        return $self["{$entityKey}"];
    }

    /**
     * Returns the entities key that will be used for storing internal refs of instance
     *
     * @param  string $key The key for entity prefix to append
     * @return string
     */
    public static function getEntityKey($key)
    {
        return self::ENTITY_KEY_PREFIX . $key;
    }

    /**
     * Returns the instance created in global scope
     *
     * @static
     * @return GlobalStorage The global storage instance
     *
     * @throws \AppserverIo\Concurrency\ExecutorService\Exception
     */
    public static function getInstance()
    {
        $globalVarName = self::getGlobalVarName();
        global $$globalVarName;
        if (is_null($$globalVarName)) {
            throw new ExecutorService\Exception(sprintf("Failed to get instance '$%s'. Please call init() in global scope first and check if PTHREADS_ALLOW_GLOBALS flag is set in specific Thread start calls.", $globalVarName));
        }
        return $$globalVarName;
    }

    /**
     * Creates a new instance of execution service with given entity class and mapped alias
     *
     * @param string $entityType The class to make use of execution service as singleton
     * @param string $alias      The alias to use for store that instance in global storage of execution service
     * @param string $autoloader The autoloader to use
     *
     * @return ExecutorService
     * @throws \AppserverIo\Concurrency\ExecutorService\Exception
     */
    public static function newFromEntity($entityType, $alias = null, $autoloader = null)
    {
        // get own instance
        $self = self::getInstance();
        // init entity key
        $entityKey = self::getEntityKey($entityType);
        // init autoloader and set default if nothing was given
        if (is_null($autoloader)) {
            $autoloader = $self->__autoloader;
        }
        
        // check alias functionality
        if (!is_null($alias)) {
            $entityKey = self::getEntityKey($alias);
            // check if alias was registered already
            if (isset($self["{$entityKey}"])) {
                throw new ExecutorService\Exception(sprintf("Entity '%s' with alias '%s' has already been created.", $entityType, $alias));
            }
        } else {
            // check if entity was registered already
            if (isset($self["{$entityKey}"])) {
                throw new ExecutorService\Exception(sprintf("Entity '%s' has already been created.", $entityType, $alias));
            }
        }
        
        // create execution service instance with entity.
        $newInstanceFromEntity = new ExecutorService($entityType, $autoloader);
        
        // set ref to local storage
        $self["{$entityKey}"] = $newInstanceFromEntity;
        
        // return it
        return $newInstanceFromEntity;
    }

    /**
     * Initializes all entity method annotations
     *
     * @param ExecutorService $executorServiceInstance An executor service instance
     * @param string          $entityClassName         The entity class name to init
     *
     * @static
     * @return array
     */
    public static function initEntityAnnotations($executorServiceInstance, $entityClassName)
    {
        // get reflection of entity class
        $reflector = new \ReflectionClass($entityClassName);
        // get all Methods
        $methods = $reflector->getMethods();
        // init method exec type mapper array
        $methodExecutionTypeMapper = array();
        // iterate all methods
        foreach ($methods as $method) {
            // set default method type
            $methodExecutionTypeMapper["::{$method->getName()}"] =  self::EX_METHOD_DEFAULT;
            // get method annotations
            preg_match_all('#@(.*?)\n#s', $method->getDocComment(), $annotations);
            // check if asynch annotation
            if (in_array('Asynchronous', $annotations[1])) {
                $methodExecutionTypeMapper["::{$method->getName()}"] = self::EX_METHOD_ASYNCHRONOUS;
            }
            // check if synch annotation
            if (in_array('Synchronized', $annotations[1])) {
                $methodExecutionTypeMapper["::{$method->getName()}"] = self::EX_METHOD_SYNCHRONIZED;
            }
        }
        // save mapper array to executor service instance
        $executorServiceInstance->__methodExecutionTypeMapper = $methodExecutionTypeMapper;
    }
}
