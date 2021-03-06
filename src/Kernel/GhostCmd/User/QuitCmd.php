<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Kernel\GhostCmd\User;

use Commune\Kernel\GhostCmd\AGhostCmd;
use Commune\Protocals\HostMsg\DefaultIntents;
use Commune\Blueprint\Framework\Command\CommandMsg;
use Commune\Blueprint\Framework\Pipes\RequestCmdPipe;


/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class QuitCmd extends AGhostCmd
{
    const SIGNATURE = 'quit';

    const DESCRIPTION = '退出会话';

    protected function handle(CommandMsg $message, RequestCmdPipe $pipe): void
    {
        $this->cloner
            ->comprehension
            ->intention
            ->setMatchedIntent(DefaultIntents::GUEST_NAVIGATE_QUIT);

        $this->goNext();
    }


}