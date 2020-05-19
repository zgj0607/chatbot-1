<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Ghost\Commands\Super;

use Commune\Blueprint\Framework\Command\CommandMsg;
use Commune\Blueprint\Framework\Pipes\RequestCmdPipe;
use Commune\Ghost\Cmd\AGhostCmd;
use Commune\Support\Arr\ArrayAndJsonAble;

/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class ScopeCmd extends AGhostCmd
{
    const SIGNATURE = 'scope';

    const DESCRIPTION = '查看用户自己的数据';

    protected function handle(CommandMsg $command, RequestCmdPipe $pipe): void
    {
        $json = $this->cloner->scope->toJson(ArrayAndJsonAble::PRETTY_JSON);
        $this->info($json);
    }


}