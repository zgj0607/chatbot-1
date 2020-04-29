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
use Commune\Blueprint\Ghost\Routing\Backward;
use Commune\Ghost\Operators\Backward\BackStep;
use Commune\Ghost\Operators\Backward\Rewind;
use Commune\Ghost\Operators\End\NoStateEnd;


/**
 * 回退的相关调度逻辑.
 *
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class IBackward implements Backward
{
    public function rewind(bool $quiet): Operator
    {
        return new Rewind($quiet);
    }

    public function backStep(int $steps): Operator
    {
        return new BackStep($steps);
    }

}