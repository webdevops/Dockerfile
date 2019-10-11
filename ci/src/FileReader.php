<?php

namespace Webdevops\Build;

use RecursiveIteratorIterator;
use RegexIterator;
use RecursiveDirectoryIterator;
use RecursiveRegexIterator;

class FileReader
{

    public function collectDockerfiles()
    {
        $dockerDir = new RecursiveDirectoryIterator(__DIR__ . '/../../docker/');
        $iterator = new RecursiveIteratorIterator($dockerDir);
        return new RegexIterator($iterator, '/^.*Dockerfile$/i', RecursiveRegexIterator::GET_MATCH);
    }

    public function getInfo(string $dockerfilePath)
    {
        $content = file_get_contents($dockerfilePath);
        preg_match('/# Dockerfile for webdevops\/(.*):(.*)/', $content, $headerMatches);
        $imageName = $headerMatches[1];
        $tagName = $headerMatches[2];
        $id = 'webdevops/' . $imageName . ':' . $tagName;
        preg_match_all('/FROM (.*)/', $content, $fromMatches);
        $node = [
            'id' => $id,
            'name' => $id,
            'image' => $imageName,
            'tag' => $tagName,
            'aliases' => [],
            'file' => $dockerfilePath,
            'parent' => 0,
        ];
        // Only internal images must be contained in build tree
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
