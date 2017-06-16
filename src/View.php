<?php

namespace Sensi\Minimal;

use Monolyth\Improse;
use Zend\Diactoros\Response\HtmlResponse;

abstract class View extends Improse\View
{
    public function render()
    {
        $html = $this->twig->render($this->template, $this->getVariables());
        if ($this->env->prod) {
            $html = preg_replace(['@^\s+@ms', '@\s+(</\w+>)$@m'], ['', '\\1'], $html);
        }
        return $html;
    }

    public function __invoke()
    {
        return new HtmlResponse($this->render());
    }
}

