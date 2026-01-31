<?php

namespace App\Notification;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

readonly class NotificationRenderer
{
    /**
     * @param Environment $twig
     */
    public function __construct(private Environment $twig)
    {
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function render(string $template, array $context): string
    {
        return $this->twig->render($template, $context);
    }
}
