<?php

namespace Sensi\Minimal;

use Monolyth\Improse;
use Zend\Diactoros\Response\HtmlResponse;
use Twig_Loader_Error;
use Monolyth\Frontal\Exception;

abstract class View extends Improse\View
{
    public function render()
    {
        try {
            $html = $this->twig->render($this->template, $this->getVariables());
            if ($this->env->prod) {
                $html = preg_replace(['@^\s+@ms', '@\s+(</\w+>)$@m'], ['', '\\1'], $html);
            }
            $html = preg_replace_callback(
                '@<title>(.*?)</title>@ms',
                function ($match) {
                    return '<title>'.strip_tags($match[1]).'</title>';
                },
                $html
            );
            return $html;
        } catch (Twig_Loader_Error $e) {
            throw new Exception(404);
        }
    }

    public function __invoke()
    {
        return new HtmlResponse($this->render());
    }
}

