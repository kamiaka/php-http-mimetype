<?php

require 'httpmimetype.php';

use PHPUnit\Framework\TestCase;

class HTTPMimeTypeTest extends TestCase {
    public function testNegotiateContentTypeString() {
        $cases = [
            [
                "type" => "text/html",
                "supports" => ["text/html", "text/plain"],
                "wont" => "text/html"
            ],
            [
                "type" => "text/html; charset=utf-8",
                "supports" => ["text/plain", "text/html"],
                "wont" => "text/html"
            ],
            [
                "type" => "text/html;level=1",
                "supports" => ["text/html"],
                "wont" => "text/html"
            ],
            [
                "type" => "text/html;level=1",
                "supports" => ["text/html; level=2"],
                "wont" => null
            ],
            [
                "type" => "text/html",
                "supports" => ["text/html;level=1"],
                "wont" => null
            ],
        ];

        foreach ($cases as $i => $tc) {
            $got = HTTPMimeType::negotiateContentTypeString($tc["type"], $tc["supports"]);

            $this->assertEquals($tc["wont"], $got, "Case: {$i}");
        }
    }

    public function testNegotiateMimeType() {
        $cases = [
            [
                "accept" => "application/x-a-b",
                "supports" => ["application/x-a", "application/x-a-b"],
                "default" => "nouse",
                "wont" => "application/x-a-b"
            ],
            [
                "accept" => "*/*",
                "supports" => ["application/x-a", "application/x-a-b"],
                "default" => "nouse",
                "wont" => "application/x-a"
            ],
            [
                "accept" => "text/*;q=0.3, text/html;q=0.7, text/html;level=1, text/html;level= 2;q=0.4, */*;q=0.5",
                "supports" => ["text/html;level=2", "text/html;level=3"],
                "default" => "nouse",
                "wont" => "text/html;level=2"
            ],
            [
                "accept" => "text/*;q=0.3, text/html;q=0.7, text/html;level=1, text/html;level=2;q=0.4, */*;q=0.5",
                "supports" => ["image/jpeg", "image/png"],
                "default" => "nouse",
                "wont" => "image/jpeg"
            ],
            [
                "accept" => "text/*",
                "supports" => ["text/plain", "text/html"],
                "default" => "nouse",
                "wont" => "text/plain"
            ],
            [
                "accept" => "text/*",
                "supports" => ["image/png", "image/jpeg", "image/gif"],
                "default" => null,
                "wont" => null
            ],
        ];

        foreach ($cases as $i => $tc) {
            $got = HTTPMimeType::negotiateAcceptTypeString($tc["accept"], $tc["supports"], $tc["default"]);

            $this->assertEquals($tc["wont"], $got, "Case: {$i}");
        }
    }

    public function testParseAcceptTypes() {
        $cases = [
            [
                "accept" => "audio/*; q=0.2, audio/basic",
                "wont" => ["audio/basic", "audio/*"],
            ],
            [
                "accept" => "text/plain; q=0.5, text/html, text/x-dvi; q=0.8, text/x-c",
                "wont" => ["text/html", "text/x-c", "text/x-dvi", "text/plain"],
            ],
            [
                "accept" => "text/*, text/html, text/html;level=1, */*",
                "wont" => ["text/html;level=1", "text/html", "text/*", "*/*"],
            ],
            [
                "accept" => "text/*;q=0.3, text/html;q=0.7, text/html;level=1, text/html;level=2;q=0.4, */*;q=0.5",
                "wont" => ["text/html;level=1", "text/html", "*/*", "text/html;level=2", "text/*"],
            ],
        ];

        foreach ($cases as $i => $tc) {
            $got = HTTPMimeType::parseAcceptTypes($tc["accept"]);

            $this->assertEquals($tc["wont"], $got, "Case: {$i}");
        }
    }

}
