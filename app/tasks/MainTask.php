<?php
use Phalcon\Cli\Task;
use common\util;
use \GuzzleHttp\Client;
use common\define\D_COMM_DEFINE;

class MainTask extends Task
{
    public function mainAction()
    {
        echo 'This is the default task and the default action' . PHP_EOL;
    }

    /**
     * @param array $params
     */
    public function testAction(array $params)
    {
        util::printFoZhu();

        printf("%s\n", D_COMM_DEFINE::SUCCESS);

    }
}