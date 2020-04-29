<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Ghost\Routing;

use Commune\Blueprint\Ghost\Operator\Operator;
use Commune\Blueprint\Ghost\Routing\Fallback;
use Commune\Blueprint\Ghost\Stage\Stage;
use Commune\Ghost\Operators\Current\CancelCurrent;
use Commune\Ghost\Operators\Current\FulfillCurrent;
use Commune\Ghost\Operators\Current\QuitCurrent;
use Commune\Ghost\Operators\Current\RejectCurrent;
use Commune\Protocals\HostMsg;


/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class IFallback implements Fallback
{
    /**
     * @var Stage
     */
    protected $stage;

    /**
     * IFallback constructor.
     * @param Stage $stage
     */
    public function __construct(Stage $stage)
    {
        $this->stage = $stage;
    }


    public function reject(HostMsg $message = null): Operator
    {
        return new RejectCurrent();
    }

    public function cancel(): Operator
    {
        return new CancelCurrent();
    }

    public function quit(): Operator
    {
        return new QuitCurrent();
    }

    public function fulfill(int $gcTurn = 0): Operator
    {
        return new FulfillCurrent($gcTurn);
    }


}