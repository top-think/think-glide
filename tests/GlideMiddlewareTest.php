<?php

namespace Slince\Glide\Tests;

use PHPUnit\Framework\TestCase;
use Slince\Glide\GlideMiddleware;
use think\Request;
use think\Response;

class GlideMiddlewareTest extends TestCase
{
    public function testFactory()
    {
        $this->assertInstanceOf(GlideMiddleware::class, GlideMiddleware::factory([
            __DIR__ . '/fixtures/source',
            __DIR__ . '/fixtures/cache'
        ]));
    }

    public function testResponse()
    {
        $request = (new Request())->create('/images/phpdish.png');

        $middleware = GlideMiddleware::factory([
            __DIR__ . '/fixtures/source',
            __DIR__ . '/fixtures/cache'
        ]);

        $response = $middleware($request, function($request){
            return new Response('ok');
        });

        $this->assertEquals(filesize(__DIR__ . '/fixtures/source/phpdish.png'), $response->header('Content-Length'));
    }
}
