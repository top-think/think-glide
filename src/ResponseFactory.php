<?php

/*
 * This file is part of the slince/think-glide
 *
 * (c) Slince <taosikai@yeah.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Slince\Glide;

use League\Flysystem\FilesystemInterface;
use League\Glide\Responses\ResponseFactoryInterface;
use think\Response;

class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * Create response.
     *
     * @param FilesystemInterface $cache
     * @param string              $path
     *
     * @return Response
     *
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function create(FilesystemInterface $cache, $path)
    {
        $contentType = $cache->getMimetype($path);
        $contentLength = $cache->getSize($path);

        $response = new Response();
        $response->data(stream_get_contents($cache->readStream($path)))
            ->header('Content-Type', $contentType)
            ->header('Content-Length', $contentLength);

        return $response;
    }
}
