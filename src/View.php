<?php

namespace Sensi\Minimal;

use Monolyth\Improse;
use Monolyth\Disclosure\Injector;
use Zend\Diactoros\Response\HtmlResponse;
use Twig_Error_Loader;
use Monolyth\Frontal\Exception;

abstract class View extends Improse\View
{
    use Injector;

    /** @var int */
    protected $status = 200;

    /** @var array */
    protected $headers = [];

    /** @var array */
    protected $excludePatterns = [];

    public function render()
    {
        $this->inject(function ($env) {});
        try {
            $html = $this->twig->render($this->template, $this->getVariables());
            if ($this->excludePatterns) {
                $html = preg_replace_callback(
                    $this->excludePatterns,
                    function ($match) {
                        return $match[1].str_replace(' ', '&nbsp;', $match[2]).$match[3];
                    },
                    $html
                );
            }
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
        } catch (Twig_Error_Loader $e) {
            throw new Exception(404);
        }
    }

    public function __invoke()
    {
        return new HtmlResponse($this->render(), $this->status, $this->headers);
    }
}

