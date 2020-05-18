<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Ghost;

use Commune\Blueprint\Ghost\Dialog;
use Commune\Blueprint\Configs\GhostConfig;
use Commune\Blueprint\Ghost;
use Commune\Blueprint\Ghost\Cloner;
use Commune\Blueprint\Ghost\Context;
use Commune\Blueprint\Ghost\Ucl;
use Commune\Contracts\Cache;
use Commune\Framework\ASession;
use Psr\Log\LoggerInterface;
use Commune\Ghost\Dialog\IStartProcess;
use Commune\Protocals\Intercom\GhostInput;
use Commune\Protocals\Intercom\GhostMsg;
use Commune\Support\Registry\OptRegistry;
use Commune\Blueprint\Framework\ReqContainer;
use Commune\Blueprint\Framework\Session\SessionStorage;


/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class ICloner extends ASession implements Cloner
{

    const SINGLETONS =  [
        'scope' => Cloner\ClonerScope::class,
        'config' => GhostConfig::class,
        'convo' => Ghost\Tools\Typer::class,
        'cache' => Cache::class,
        'auth' => Ghost\Auth\Authority::class,
        'mind' => Ghost\Mindset::class,
        'runtime' => Ghost\Runtime\Runtime::class,
        'registry' => OptRegistry::class,
        'logger' => Cloner\ClonerLogger::class,
    ];

    /*------- components -------*/

    /**
     * @var Ghost
     */
    protected $ghost;

    /**
     * @var GhostConfig
     */
    protected $ghostConfig;

    /**
     * @var GhostInput
     */
    protected $ghostInput;

    /*------- cached -------*/

    /**
     * @var string
     */
    protected $clonerId;

    /**
     * @var int
     */
    protected $expire;

    /**
     * @var Context[]
     */
    protected $contexts = [];

    /**
     * @var GhostMsg[]
     */
    protected $outputs = [];

    /**
     * @var GhostInput[]
     */
    protected $asyncInputs = [];

    /**
     * @var bool
     */
    protected $silent = false;

    public function __construct(Ghost $ghost, ReqContainer $container, GhostInput $input)
    {
        $this->ghost = $ghost;
        $this->ghostConfig = $ghost->getConfig();
        $this->ghostInput = $input;
        // id
        $this->clonerId = $input->getCloneId();

        // expire
        $this->expire = $this->ghostConfig->sessionExpire;
        parent::__construct($container, $input->getSessionId());
    }


    public function getClonerId(): string
    {
        return $this->clonerId;
    }

    /*-------- sessionId ---------*/

    public function getSessionId() : string
    {
        if (isset($this->sessionId)) {
            return $this->sessionId;
        }

        if ($this->isStateless()) {
            $sessionId = $this->ghostInput->getSessionId()
                ?? $this->makeSessionId();
            return $this->sessionId = $sessionId;
        }

        $inputSid = $this->ghostInput->getSessionId();
        $cachedSid = $this->getSessionIdFromCache();

        if (empty($cachedSid)) {
            $sessionId = $inputSid ?? $this->makeSessionId();

        } elseif(empty($inputSid)) {
            $sessionId = $cachedSid;
            $this->ghostInput->withSessionId($sessionId);
        } else {
            $sessionId = $inputSid;
        }

        return $this->sessionId = $sessionId;
    }

    protected function makeSessionId() : string
    {
        $clonerId = $this->getClonerId();
        $messageId = $this->ghostInput->getMessageId();
        return sha1("cloner:$clonerId:message:$messageId");
    }

    protected function getSessionIdFromCache() : ? string
    {
        $key = $this->getSessionCacheKey();
        return $this->cache->get($key);
    }

    protected function cacheSessionId(string $sessionId) : void
    {
        $key = $this->getSessionCacheKey();
        $this->cache->set($key, $sessionId, $this->getSessionExpire());
    }

    protected function getSessionCacheKey() : string
    {
        $cloneId = $this->getClonerId();
        return "clone:$cloneId:sessionId";
    }

    /*-------- contextual ---------*/

    public function getContextualQuery(string $contextName, array $query = null): array
    {
        $contextDef = $this->mind->contextReg()->getDef($contextName);
        $scopes = $contextDef->getScopes();
        $map = $this->scope->getLongTermDimensionsDict($scopes);

        $query = $query ?? [];
        $query = $contextDef->getParamsManager()->parseQuery($query);

        return $query + $map;
    }

    public function getContextualEntities(string $contextName): array
    {
        $contextDef = $this->mind->contextReg()->getDef($contextName);

        $entities = $this->ghostInput
            ->comprehension
            ->intention
            ->getIntentEntities($contextName);

        if (empty($entities)) {
            return [];
        }

       return $contextDef->getParamsManager()->parseIntentEntities($entities);
    }

    public function getContext(Ucl $ucl): Context
    {
        $contextId = $ucl->getContextId();

        if (isset($this->contexts[$contextId])) {
            return $this->contexts[$contextId];
        }

        $contextName = $ucl->contextName;
        $contextDef = $this->mind->contextReg()->getDef($contextName);

        $context = $contextDef->wrapContext($this, $ucl);
        $entities = $this->getContextualEntities($contextName);

        if (!empty($entities)) {
            $context->merge($entities);
        }

        return $this->contexts[$contextId] = $context;
    }


    /*-------- properties ---------*/

    protected function getProtocalOptions(): array
    {
        return $this->ghostConfig->protocals;
    }

    public function getName(): string
    {
        return $this->config->name;
    }

    public function getStorage(): SessionStorage
    {
        return $this->storage;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }


    /*------- dialog manager -------*/

    public function runDialogManager(Dialog $dialog = null): bool
    {
        $next = $dialog ?? new IStartProcess($this);

        try {

            //$tracer = $this->runtime->trace;

            while(isset($next)) {

                $next = $next->tick();

                if ($next instanceof Dialog\Finale) {
                    $next->tick();
                    return true;
                }
            }

        } catch (Ghost\Exceptions\TooManyRedirectsException $e) {

        } catch (\Throwable $e) {

        }

        return false;
    }


    /*------- getter -------*/

    public function __get($name)
    {
        if ($name === 'ghost') {
            return $this->ghost;
        }

        if ($name === 'ghostConfig') {
            return $this->ghostConfig;
        }

        if ($name === 'ghostInput') {
            return $this->ghostInput;
        }

        return parent::__get($name);
    }

    /*------- cache -------*/

    protected function getGhostClonerLockerKey() : string
    {
        $ghostId = $this->ghostConfig->id;
        $clonerId = $this->getClonerId();
        return "ghost:$ghostId:clone:$clonerId:locker";
    }

    public function lock(int $second): bool
    {
        $ttl = $this->ghostConfig->sessionLockerExpire;
        if ($ttl > 0) {
            $locker = $this->getGhostClonerLockerKey();
            return $this->cache->lock($locker, $ttl);
        } else {
            return true;
        }

    }

    public function isLocked(): bool
    {
        $ttl = $this->ghostConfig->sessionLockerExpire;
        if ($ttl > 0) {
            $locker = $this->getGhostClonerLockerKey();
            return $this->cache->has($locker);
        }
        return false;
    }

    public function unlock(): bool
    {
        $ttl = $this->ghostConfig->sessionLockerExpire;
        if ($ttl > 0) {
            $locker = $this->getGhostClonerLockerKey();
            return $this->cache->unlock($locker);
        }
        return true;
    }

    public function getSessionExpire(): int
    {
        return $this->expire;
    }

    public function setSessionExpire(int $seconds): void
    {
        $this->expire = $seconds;
    }

    /*------- output -------*/

    public function silence(bool $silent = true): void
    {
        $this->silent = $silent;
    }

    public function output(GhostMsg $output, GhostMsg ...$outputs): void
    {
        array_unshift($outputs, $output);
        if (!$this->silent) {
            $this->outputs = array_reduce(
                $outputs,
                function($outputs, GhostMsg $output){
                    $outputs[] = $output->withSessionId($this->getSessionId());
                    return $outputs;
                },
                $this->outputs
            );
        }
    }

    public function getOutputs(): array
    {
        return $this->outputs;
    }

    public function asyncInput(GhostInput $ghostInput): void
    {
        if (!$this->silent) {
            $this->asyncInputs[] = $ghostInput;
        }
    }

    public function getAsyncInput(): array
    {
        return $this->asyncInputs;
    }


    /*------- flush -------*/

    protected function flushInstances(): void
    {
        $this->ghost = null;
        $this->ghostConfig = null;
        $this->ghostInput = null;
        $this->contexts = [];
        $this->outputs = [];
        $this->asyncInputs = [];
    }

    protected function saveSession(): void
    {
        // runtime 更新.
        if (!$this->isSingletonInstanced('runtime')) {
            $this->runtime->save();
        }

        // storage 更新.
        if ($this->isSingletonInstanced('storage')) {
            $this->storage->save();
        }

        // 更新 sessionId 缓存.
        if (isset($this->sessionId)) {
            $this->cacheSessionId($this->sessionId);
        }
    }

}