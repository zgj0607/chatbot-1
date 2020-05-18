<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Ghost\Handlers;

use Commune\Ghost\ClonePipes;
use Commune\Blueprint\Ghost\Cloner;
use Commune\Blueprint\Ghost\Request\GhostRequest;
use Commune\Blueprint\Ghost\Request\GhostResponse;
use Commune\Blueprint\Framework\Pipes\RequestPipe;
use Commune\Blueprint\Framework\Request\AppResponse;


/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class GhostRequestHandler
{
    /**
     * @var string[]
     */
    protected $middleware = [
        // 检查消息类型
        ClonePipes\CloneMessagerPipe::class,
        // api 响应
        ClonePipes\CloneApiHandlePipe::class,
        // locker
        ClonePipes\CloneLockerPipe::class,

    ];

    /**
     * @var Cloner
     */
    protected $cloner;

    /**
     * RequestHandler constructor.
     * @param Cloner $cloner
     */
    public function __construct(Cloner $cloner)
    {
        $this->cloner = $cloner;
    }


    public function __invoke(GhostRequest $request) : GhostResponse
    {
        if ($request->isStateless()) {
            $this->cloner->noState();
        }

        $end = function(GhostRequest $request) : GhostResponse {
            return $request->fail(AppResponse::NO_CONTENT);
        };

        if (empty($this->middleware)) {
            return $end($request);
        }

        $pipeline = $this->cloner->buildPipeline(
            $this->middleware,
            RequestPipe::HANDLER_FUNC,
            $end
        );

        return $pipeline($request);
    }

}