<?php

namespace Tests\Feature;

use Mockery;
use Tests\TestCase;
use App\Models\Source;
use App\Models\Document;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use App\Source\Types\WebSiteDocument;
use Illuminate\Support\Facades\Storage;

class WebSiteDocumentTest extends TestCase
{
    public function test_gets_file()
    {
        $source = Source::factory()->webDocumentMetaData()->create();

        Storage::fake('projects');

        $webFileSourceType = new WebSiteDocument($source);

        $html = File::get(base_path('tests/fixtures/example.html'));

        Http::fake([
            'en.wikipedia.com/*' => Http::response($html, 200),
        ]);

        $webFileSourceType->handle();

        Http::assertSentCount(1);

        $to = sprintf('%d/sources/%d/Laravel.html',
            $source->project_id, $source->id);
        Storage::disk('projects')->assertExists($to);

    }

    public function test_makes_document()
    {
        $source = Source::factory()->webDocumentMetaData()->create();

        Storage::fake('projects');
        $webFileSourceType = new WebSiteDocument($source);

        Http::fake([
            'en.wikipedia.com/*' => Http::response('foo', 200),
        ]);

        $this->assertDatabaseCount('documents', 0);
        $webFileSourceType->handle();

        $this->assertDatabaseCount('documents', 1);

    }

    public function test_makes_document_once_with_name()
    {
        $source = Source::factory()->webDocumentMetaData()->create();

        Storage::fake('projects');
        $webFileSourceType = new WebSiteDocument($source);

        Http::fake([
            'wikipedia.com/*' => Http::response('foo', 200),
        ]);

        $this->assertDatabaseCount('documents', 0);
        $webFileSourceType->handle();

        $this->assertDatabaseCount('documents', 1);

        $document = Document::first();
        $this->assertEquals("Laravel.html", $document->guid);

        $webFileSourceType->handle();
        $this->assertDatabaseCount('documents', 1);
    }

    protected function mockFunction($functionName, $returnValue)
    {
        $mock = Mockery::mock();
        $mock->shouldReceive('__invoke')->andReturn($returnValue);
        $this->app->instance($functionName, $mock);
    }
}
