<?php

namespace Sensi\Minimal;

use Monolyth\Improse;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Twig_Error_Loader;
use Monolyth\Frontal\Exception;
use Monolyth\Envy;
use Twig;

/**
 * Custom base view with sensible Sensi defaults:
 * - strip unnecessary whitespace in production;
 * - render using Twig;
 * - throw sensible errors.
 */
abstract class View extends Improse\View
{
    /**
     * @var int
     *
     * HTTP status code to send. Defaults to 200.
     */
    protected int $status = 200;

    /**
     * @var array
     *
     * Optional hash of extra headers to send. These are in the form
     * `$key => $value` and will be normalised by Diactoros.
     */
    protected array $headers = [];

    /**
     * @var array
     *
     * Array of regex patterns that should be "excluded" from minimization. In
     * effect, this means spaces will be replaced by &nbsp;. This is useful e.g.
     * when showing source code, or content styled with `white-space: pre`.
     *
     * Each regex should contain 3 subpatterns: the opening tag, the content and
     * the closing tag, and must be a fully formed PHP regex string (including
     * delimiters and optional modifiers).
     *
     * <code>
     * protected $excludePatterns = ['@(<div class="pre">)(.*?)(</div>)@ms'];
     * </code>
     */
    protected array $excludePatterns = [];

    /**
     * Render the template as a string, stripping whitespace on production.
     *
     * @return string
     * @throw Monolyth\Frontal\Exception on any error.
     * @throw Twig_Error_Loader
     */
    public function render() : string
    {
        if (!isset($this->env, $this->twig)
            || ! $this->env instanceof Envy\Environment
            || ! $this->twig instanceof Twig\Environment
        ) {
            throw new DependencyException("Please make sure your view has an \$env and a \$twig member.");
        }
        try {
            $html = $this->twig->render($this->template, $this->getVariables());
            if ($this->env->prod) {
                if ($this->excludePatterns) {
                    $html = preg_replace_callback(
                        $this->excludePatterns,
                        function ($match) {
                            $match[2] = str_replace("\n", '___NEW$LINE___', $match[2]);
                            return $match[1].str_replace(' ', '&nbsp;', $match[2]).$match[3];
                        },
                        $html
                    );
                }
                $html = preg_replace(['@^\s+@ms', '@\s+(</\w+>)$@m'], ['', '\\1'], $html);
                if ($this->excludePatterns) {
                    $html = str_replace('___NEW$LINE___', "\n", $html);
                }
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
            if ($this->env->prod) {
                throw new Exception(404);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @return Psr\Http\Message\ResponseInterface
     */
    public function __invoke() : ResponseInterface
    {
        return new HtmlResponse($this->render(), $this->status, $this->headers);
    }
}

