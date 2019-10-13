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
        $isToolImage = in_array('/' . $imageName, $this->_settings['dockerTest']['toolImages']);
        preg_match_all('/' . $this->_settings['dockerTest']['configuration']['imageConfigurationRegex'] . '/', $id, $serverSpecMatches);
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
                'DOCKER_IS_TOOLIMAGE' => $isToolImage ? '1' : '0',
                'DOCKER_TAG' => $tagName,
                'OS_FAMILY' => $serverSpecMatches['OS_FAMILY'][0] ?? $this->_settings['dockerTest']['configuration']['default']['OS_FAMILY'],
                'OS_VERSION' => $serverSpecMatches['OS_VERSION'][0] ?? $this->_settings['dockerTest']['configuration']['default']['OS_VERSION'],
            ],
        ];
        // Additional serverSpec variables
        foreach ($this->_settings['dockerTest']['configuration']['image'] as $regex => $variables) {
            if (preg_match('#' . $regex . '#i', $id)) {
                $node['serverspec'] = array_merge($node['serverspec'], $variables);
            }
        }
        // Only internal images must be contained in build tree
        preg_match_all('/FROM (.*)/', $content, $fromMatches);
        $parentImage = array_pop($fromMatches[1]);
        if (strpos($parentImage, 'webdevops/') === 0) {
            $node['parent'] = $parentImage;
        }
        // Treat *-official images
        if (strpos($id, '-official:') !== false) {
            $node['aliases'][] = $id;
            $node['id'] = $node['name'] = str_replace('-official:', ':', $id);
            $node['image'] = str_replace('-official', '', $node['image']);
        }
        return $node;
    }

}
