<?php

class IndexController extends ControllerBase
{
    protected $filterList = [
        "index"
    ];

    public function indexAction()
    {
        $a = [1, 2, 3, 4];
        $b = [2, 3, 5, 7];
        var_dump(array_diff($a, $b));
    }

}

