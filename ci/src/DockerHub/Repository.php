<?php

declare(strict_types=1);

namespace Webdevops\Build\DockerHub;

/**
 * A repository of this project on Docker Hub, e.g. webdevops/php-nginx, with the short description that Docker Hub
 * shows in its search and the page that ci/console generate:dockerhub writes for it.
 */
final readonly class Repository
{
    /**
     * The directory of the pages, relative to the repository root.
     */
    public const string DIRECTORY = 'docs/dockerhub';

    /**
     * @param string $name the repository without "webdevops/", e.g. php-nginx
     * @param bool $built whether the image is still built, or its page warns that it is not
     */
    public function __construct(
        public string $name,
        public string $shortDescription,
        public bool $built,
    ) {
    }

    /**
     * The page of the repository, relative to the repository root, e.g. docs/dockerhub/php-nginx.md.
     */
    public function pagePath(): string
    {
        return self::DIRECTORY . '/' . $this->name . '.md';
    }
}
