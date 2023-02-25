<?php

declare(strict_types=1);

namespace mirzaev\site\account\views;

use mirzaev\minimal\controller;

use Twig\Loader\FilesystemLoader;
use Twig\Environment as view;

/**
 * Менеджер представлений
 *
 * @package mirzaev\site\account\controllers
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
final class manager extends controller
{
    public function render(string $file, array $vars = []): ?string
    {
        // Генерация представления
        return (new view(new FilesystemLoader(VIEWS)))->render($file, $vars);
    }
}
