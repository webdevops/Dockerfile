<?php

namespace Webdevops\Build;

use RecursiveIteratorIterator;
use RegexIterator;
use RecursiveDirectoryIterator;
use RecursiveRegexIterator;
use Symfony\Component\Yaml\Yaml;

class FileReader
{
    private $_settings;

    public function __construct()
    {
        $this->_settings = Yaml::parseFile(__DIR__ . '/../../conf/console.yml');
    }

    public function collectDockerfiles()
    {
        $dockerDir = new RecursiveDirectoryIterator(__DIR__ . '/../../docker/');
        $iterator = new RecursiveIteratorIterator($dockerDir);
        return new RegexIterator($iterator, '/^.*Dockerfile$/i', RecursiveRegexIterator::GET_MATCH);
    }

    public function getInfo(string $dockerfilePath)
    {
        $content = file_get_contents($dockerfilePath);
        // Extract info from file header
        preg_match('/# Dockerfile for webdevops\/(.*):(.*)/', $content, $headerMatches);
        $imageName = $headerMatches[1];
        $tagName = $headerMatches[2];
        $id = 'webdevops/' . $imageName . ':' . $tagName;
        $regex = '/' . $this->_settings['dockerTest']['configuration']['imageConfigurationRegex'] . '/';
        preg_match_all($regex, $id, $serverSpecMatches);
        $node = [
            'id' => $id,
            'name' => $id,
            'image' => $imageName,
            'tag' => $tagName,
            'aliases' => [],
            'file' => $dockerfilePath,
            'parent' => 0,
            'serverspec' => [
                'DOCKER_IMAGE' => $id,
                'DOCKER_TAG' => $tagName,
                'OS_FAMILY' => $serverSpecMatches['OS_FAMILY'][0] ?? $this->_settings['dockerTest']['configuration']['default']['OS_FAMILY'],
                'OS_VERSION' => $serverSpecMatches['OS_VERSION'][0] ?? $this->_settings['dockerTest']['configuration']['default']['OS_VERSION'],
            ],
        ];
        // Additional serverSpec variables (only first match)
        foreach ($this->_settings['dockerTest']['configuration']['image'] as $regex => $variables) {
            if (preg_match('#' . $regex . '#i', $id)) {
                $node['serverspec'] = array_merge($node['serverspec'], $variables);
                break;
            }
        }
        // Only internal images must be contained in build tree
        preg_match_all('/FROM (.*)/', $content, $fromMatches);
        $parentImage = array_pop($fromMatches[1]);
        if (strpos($parentImage, 'webdevops/') === 0) {
            if (str_ends_with($parentImage, ':latest')) {
                $parentImage = str_replace(':latest', ':' . $this->_settings['docker']['autoLatestTag'], $parentImage);
            }
            $node['parent'] = $parentImage;
        } else if ($node['id'] !== 'webdevops/toolbox:latest') {
            $node['parent'] = 'webdevops/toolbox:latest';
        }
        // Treat *-official images
        if (strpos($id, '-official:') !== false) {
            $node['aliases'][] = $id;
            $node['id'] = $node['name'] = str_replace('-official:', ':', $id);
            $node['image'] = str_replace('-official', '', $node['image']);
        }
        if ($tagName === $this->_settings['docker']['autoLatestTag']) {
            $node['aliases'][] = str_replace(':' . $tagName, ':latest', $id);
        }
        return $node;
    }

}
