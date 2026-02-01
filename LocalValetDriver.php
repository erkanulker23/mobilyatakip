<?php

/**
 * Valet driver: NestJS proxy (index.php → port 3000)
 * Bu proje kökündeki index.php tüm istekleri Node uygulamasına yönlendirir.
 */
class LocalValetDriver extends \Valet\Drivers\BasicValetDriver
{
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists($sitePath . '/index.php') || file_exists($sitePath . '/public/index.php');
    }

    public function isStaticFile(string $sitePath, string $siteName, string $uri): bool
    {
        if ($this->isActualFile($staticFilePath = $sitePath . $uri)) {
            return $staticFilePath;
        }
        if ($this->isActualFile($staticFilePath = $sitePath . '/public' . $uri)) {
            return $staticFilePath;
        }
        return false;
    }

    public function frontControllerPath(string $sitePath, string $siteName, string $uri): string
    {
        return file_exists($sitePath . '/public/index.php')
            ? $sitePath . '/public/index.php'
            : $sitePath . '/index.php';
    }
}
